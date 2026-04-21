<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Airdrop;
use App\Models\Tag;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AirdropController extends Controller
{
    /**
     * Paginated airdrops list; filter by recipient account (users.uid OR users.email, both exact match), or create_rollover.
     * Resolves user with first() then filters by user_id (indexed lookup, no EXISTS on airdrops).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string|max:255',
            'create_rollover' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Airdrop::query()->with(['user']);

        if ($request->filled('account')) {
            $account = trim((string) $request->input('account'));
            $user = User::query()
                ->where(function ($q) use ($account) {
                    $q->where('uid', $account)->orWhere('email', $account);
                })
                ->first();

            if ($user === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('user_id', $user->id);
            }
        }

        if ($request->exists('create_rollover')) {
            $query->where('create_rollover', $request->boolean('create_rollover'));
        }

        $query->orderByDesc('id');

        $perPage = $request->get('per_page', 15);
        $items = $query->paginate($perPage);

        return $this->responseListWithPaginator($items, null);
    }

    /**
     * Batch-create airdrops (one row per resolved user). Optionally attach a tag to each recipient.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type' => 'required|string|in:uid,email',
            'accounts' => 'required|array|min:1|max:5000',
            'accounts.*' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:16',
            'create_rollover' => 'nullable|boolean',
            'tag_id' => 'nullable|integer|min:1',
            'remark' => 'nullable|string|max:2000',
        ]);

        if (!isset($validated['currency']) || trim((string) $validated['currency']) === '') {
            $validated['currency'] = config('app.currency', 'USD');
        }

        $tagId = $validated['tag_id'] ?? null;
        if ($tagId !== null && !Tag::query()->whereKey($tagId)->exists()) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $seen = [];
        $orderedAccounts = [];
        foreach ($validated['accounts'] as $raw) {
            $acc = trim((string) $raw);
            if ($acc === '') {
                continue;
            }
            if (isset($seen[$acc])) {
                continue;
            }
            $seen[$acc] = true;
            $orderedAccounts[] = $acc;
        }

        if ($orderedAccounts === []) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $column = $validated['account_type'] === 'uid' ? 'uid' : 'email';
        $usersByKey = User::query()
            ->whereIn($column, $orderedAccounts)
            ->get()
            ->keyBy($column);

        $createRollover = (bool) ($validated['create_rollover'] ?? false);
        $remark = $validated['remark'] ?? null;

        $created = [];
        $skipped = [];

        $balanceService = new BalanceService();
        $notificationService = app(NotificationService::class);

        DB::transaction(function () use (
            $orderedAccounts,
            $usersByKey,
            $validated,
            $createRollover,
            $remark,
            $tagId,
            $balanceService,
            &$created,
            &$skipped
        ): void {
            foreach ($orderedAccounts as $account) {
                $user = $usersByKey->get($account);
                if ($user === null) {
                    $skipped[] = [
                        'account' => $account,
                        'reason' => 'user_not_found',
                    ];
                    continue;
                }

                $airdrop = Airdrop::create([
                    'user_id' => $user->id,
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'create_rollover' => $createRollover,
                    'remark' => $remark,
                ]);

                $balanceService->applyForAirdrop($airdrop);

                if ($tagId !== null) {
                    $user->tags()->syncWithoutDetaching([$tagId]);
                }

                $created[] = $airdrop->load('user');
            }
        });

        foreach ($created as $airdrop) {
            if ((int) bccomp((string) $airdrop->amount, '0', 8) > 0) {
                $notificationService->createAirdropNotification(
                    $airdrop->user_id,
                    (float) $airdrop->amount,
                    $airdrop->currency,
                    $airdrop->id
                );
            }
        }

        return $this->responseItem([
            'created_count' => count($created),
            'skipped' => $skipped,
            'items' => $created,
        ]);
    }
}
