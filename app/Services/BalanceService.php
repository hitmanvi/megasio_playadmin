<?php

namespace App\Services;

use App\Events\BalanceChanged;
use App\Models\Airdrop;
use App\Models\Balance;
use App\Models\Rollover;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    protected $transactionService;

    public function __construct()
    {
        $this->transactionService = new TransactionService();
    }
    /**
     * Get balance for specific user and currency.
     */
    public function getBalance(int $userId, string $currency): ?Balance
    {
        return Balance::where('user_id', $userId)
                     ->where('currency', $currency)
                     ->first();
    }

    /**
     * 创建用户默认币种的 balance（如果不存在）
     *
     * @param int $userId
     * @param string|null $currency 币种，如果为 null 则使用 app.currency 配置
     * @return Balance
     */
    public function createDefaultBalance(int $userId, ?string $currency = null): Balance
    {
        $currency = $currency ?? config('app.currency', 'USD');
        
        return Balance::firstOrCreate(
            [
                'user_id' => $userId,
                'currency' => $currency,
            ],
            [
                'available' => 0,
                'frozen' => 0,
                'version' => 0,
            ]
        );
    }

    /**
     * Create or update balance with optimistic locking.
     */
    public function updateBalance(int $userId, string $currency, float $amount, string $operation = 'add', string $type = 'available'): Balance
    {
        $balance = $this->getBalance($userId, $currency);
        
        if (!$balance) {
            $balance = Balance::create([
                'user_id' => $userId,
                'currency' => $currency,
                'available' => 0,
                'frozen' => 0,
                'version' => 0,
            ]);
        }

        // Update existing balance with optimistic locking
        $newAvailable = $balance->available;
        $newFrozen = $balance->frozen;
        
        if ($type === 'available') {
            if ($operation === 'subtract' && $balance->available < $amount) {
                throw new \Exception("Insufficient balance");
            }
            $newAvailable = $operation === 'add' 
                ? $balance->available + $amount 
                : $balance->available - $amount;
        } else {
            if ($operation === 'subtract' && $balance->frozen < $amount) {
                throw new \Exception("Insufficient balance");
            }
            $newFrozen = $operation === 'add' 
                ? $balance->frozen + $amount 
                : $balance->frozen - $amount;
        }

        $updated = Balance::where('id', $balance->id)
                         ->where('version', $balance->version)
                         ->update([
                             'available' => $newAvailable,
                             'frozen' => $newFrozen,
                             'version' => $balance->version + 1,
                         ]);

        if (!$updated) {
            throw new \Exception("Balance update failed");
        }

        // 重新加载余额
        $updatedBalance = $balance->fresh();
        
        // 触发余额变动事件（传递 user_id，查询延迟到监听器）
        event(new BalanceChanged($userId, $updatedBalance, $amount, $operation, $type));

        return $updatedBalance;
    }

    /**
     * Increment available balance.
     */
    public function increment(int $userId, string $currency, float $amount, string $type = 'available'): Balance
    {
        return $this->updateBalance($userId, $currency, $amount, 'add', $type);
    }

    /**
     * Decrement available balance.
     */
    public function decrement(int $userId, string $currency, float $amount, string $type = 'available'): Balance
    {
        return $this->updateBalance($userId, $currency, $amount, 'subtract', $type);
    }

    /**
     * Unfreeze amount from frozen balance.
     */
    public function unfreezeAmount(int $userId, string $currency, float $amount): bool
    {
        $balance = $this->getBalance($userId, $currency);
        
        if (!$balance || $balance->frozen < $amount) {
            throw new \Exception("Insufficient balance");
        }

        return $this->updateBalance($userId, $currency, $amount, 'subtract', 'frozen') &&
               $this->updateBalance($userId, $currency, $amount, 'add', 'available');
    }

    public function rejectWithdraw(int $userId, string $currency, float $amount, int $withdrawId): array
    {
        return DB::transaction(function () use ($userId, $currency, $amount, $withdrawId) {
            $balance = $this->updateBalance($userId, $currency, $amount, 'subtract', 'frozen');
            $balance = $this->updateBalance($userId, $currency, $amount, 'add', 'available')->fresh();

            $transaction = $this->transactionService->createTransaction(
                $userId,
                $currency,
                $amount,
                (float)$balance->available,
                Transaction::TYPE_WITHDRAWAL_UNFREEZE,
                $withdrawId,
                "Withdrawal rejected, funds returned."
            );

            return [
                'balance' => $balance,
                'transaction' => $transaction,
            ];
        });
    }

    /**
     * Apply an airdrop row: adjust available balance, optionally log transaction, optionally create rollover (1x on credited amount).
     * New rollover is active only when the user has no other active rollover for the same currency; otherwise pending.
     */
    public function applyForAirdrop(Airdrop $airdrop): array
    {
        $userId = $airdrop->user_id;
        $currency = $airdrop->currency;
        $amountStr = (string) $airdrop->amount;

        $this->createDefaultBalance($userId, $currency);

        $cmp = (int) bccomp($amountStr, '0', 8);

        if ($cmp > 0) {
            $balance = $this->increment($userId, $currency, (float) $amountStr);
        } elseif ($cmp < 0) {
            $balance = $this->decrement($userId, $currency, abs((float) $amountStr));
        } else {
            $balance = $this->getBalance($userId, $currency)
                ?? $this->createDefaultBalance($userId, $currency);
        }

        $transaction = null;
        if ($cmp !== 0) {
            $transaction = $this->transactionService->createTransaction(
                $userId,
                $currency,
                (float) $amountStr,
                (float) $balance->available,
                Transaction::TYPE_AIRDROP,
                (string) $airdrop->id,
                'Airdrop'
            );
        }

        $rollover = null;
        if ($airdrop->create_rollover && $cmp > 0) {
            $requiredWager = bcmul($amountStr, '1', 8);
            $hasOtherActive = Rollover::query()
                ->where('user_id', $userId)
                ->where('currency', $currency)
                ->where('status', Rollover::STATUS_ACTIVE)
                ->exists();
            $rollover = Rollover::create([
                'user_id' => $userId,
                'source_type' => Rollover::SOURCE_TYPE_AIRDROP,
                'related_id' => $airdrop->id,
                'currency' => $currency,
                'amount' => $amountStr,
                'required_wager' => $requiredWager,
                'current_wager' => '0',
                'status' => $hasOtherActive ? Rollover::STATUS_PENDING : Rollover::STATUS_ACTIVE,
            ]);
        }

        return [
            'balance' => $balance,
            'transaction' => $transaction,
            'rollover' => $rollover,
        ];
    }

}
