<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use App\Models\Withdraw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PendingReviewSummaryController extends Controller
{
    private const CACHE_KEY_PREFIX = 'admin_pending_review_snapshot:';

    private const CACHE_TTL_SECONDS = 90 * 24 * 3600;

    /**
     * 待处理提现 / 待审核 KYC 数量；快照记录各自待处理集合中最大的 id（最新一条 pending），据此判断是否有新增。
     */
    public function __invoke(Request $request): JsonResponse
    {
        $admin = $request->user();

        $withdrawPendingCount = Withdraw::query()
            ->where('status', Withdraw::STATUS_PENDING)
            ->count();

        $kycPendingReviewCount = Kyc::query()
            ->whereIn('status', [
                Kyc::STATUS_PENDING,
                Kyc::STATUS_ADVANCED_PENDING,
                Kyc::STATUS_ENHANCED_PENDING,
            ])
            ->count();

        $withdrawPendingMaxId = $this->nullableMaxId(
            Withdraw::query()->where('status', Withdraw::STATUS_PENDING)
        );

        $kycPendingMaxId = $this->nullableMaxId(
            Kyc::query()->whereIn('status', [
                Kyc::STATUS_PENDING,
                Kyc::STATUS_ADVANCED_PENDING,
                Kyc::STATUS_ENHANCED_PENDING,
            ])
        );

        $cacheKey = self::CACHE_KEY_PREFIX.$admin->getKey();
        $hadSnapshot = Cache::has($cacheKey);
        $snapshot = Cache::get($cacheKey);

        [$prevWithdrawMaxId, $prevKycMaxId] = $this->previousMaxIdsFromSnapshot($snapshot);

        $hasNewWithdraw = $hadSnapshot && $this->hasNewPendingMax($withdrawPendingMaxId, $prevWithdrawMaxId);
        $hasNewKyc = $hadSnapshot && $this->hasNewPendingMax($kycPendingMaxId, $prevKycMaxId);

        $lastSnapshot = $snapshot === null || ! is_array($snapshot)
            ? null
            : [
                'withdraw_pending_max_id' => $prevWithdrawMaxId,
                'kyc_pending_max_id' => $prevKycMaxId,
                'recorded_at' => $snapshot['recorded_at'] ?? null,
            ];

        Cache::put($cacheKey, [
            'withdraw_pending_max_id' => $withdrawPendingMaxId,
            'kyc_pending_max_id' => $kycPendingMaxId,
            'recorded_at' => now()->toIso8601String(),
        ], self::CACHE_TTL_SECONDS);

        return $this->responseItem([
            'withdraw_pending_count' => $withdrawPendingCount,
            'kyc_pending_review_count' => $kycPendingReviewCount,
            'has_new_withdraw_pending' => $hasNewWithdraw,
            'has_new_kyc_pending' => $hasNewKyc,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function nullableMaxId($query): ?int
    {
        $raw = $query->max('id');

        return $raw === null ? null : (int) $raw;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function previousMaxIdsFromSnapshot(mixed $snapshot): array
    {
        if (! is_array($snapshot)) {
            return [null, null];
        }

        // 旧版只存 count，与 max id 语义不一致，视为无基线
        if (
            array_key_exists('withdraw_pending_count', $snapshot)
            && ! array_key_exists('withdraw_pending_max_id', $snapshot)
        ) {
            return [null, null];
        }

        $w = array_key_exists('withdraw_pending_max_id', $snapshot) ? $snapshot['withdraw_pending_max_id'] : null;
        $k = array_key_exists('kyc_pending_max_id', $snapshot) ? $snapshot['kyc_pending_max_id'] : null;

        return [
            $w === null ? null : (int) $w,
            $k === null ? null : (int) $k,
        ];
    }

    private function hasNewPendingMax(?int $currentMaxId, ?int $previousMaxId): bool
    {
        if ($currentMaxId === null) {
            return false;
        }

        if ($previousMaxId === null) {
            return true;
        }

        return $currentMaxId > $previousMaxId;
    }
}
