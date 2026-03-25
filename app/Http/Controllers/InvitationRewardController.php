<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationReward;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class InvitationRewardController extends Controller
{
    /**
     * Invitation reward stats for a user as inviter (resolved by users.uid).
     */
    public function stats(string $uid): JsonResponse
    {
        $user = User::query()->where('uid', $uid)->firstOrFail();

        $inviteCount = Invitation::query()->where('inviter_id', $user->id)->count();

        $totalRewardAmount = (string) (InvitationReward::query()
            ->where('user_id', $user->id)
            ->where('status', InvitationReward::STATUS_PAID)
            ->sum('reward_amount') ?? '0');

        $depositStarterRewardCount = InvitationReward::query()
            ->where('user_id', $user->id)
            ->where('source_type', InvitationReward::SOURCE_TYPE_DEPOSIT_STARTER)
            ->where('status', InvitationReward::STATUS_PAID)
            ->count();

        $depositAdvancedRewardCount = InvitationReward::query()
            ->where('user_id', $user->id)
            ->where('source_type', InvitationReward::SOURCE_TYPE_DEPOSIT_ADVANCED)
            ->where('status', InvitationReward::STATUS_PAID)
            ->count();

        $paidStatus = InvitationReward::STATUS_PAID;
        $bySourceRows = InvitationReward::query()
            ->where('user_id', $user->id)
            ->where('status', $paidStatus)
            ->selectRaw('source_type, SUM(reward_amount) as total')
            ->groupBy('source_type')
            ->get();

        $rewardsBySourceType = [];
        foreach ($bySourceRows as $row) {
            $rewardsBySourceType[$row->source_type] = (string) $row->total;
        }

        return $this->responseItem([
            'user_id' => $user->id,
            'uid' => $user->uid,
            'invite_count' => $inviteCount,
            'total_reward_amount' => $totalRewardAmount,
            'deposit_starter_reward_count' => $depositStarterRewardCount,
            'deposit_advanced_reward_count' => $depositAdvancedRewardCount,
            'rewards_by_source_type' => $rewardsBySourceType,
        ]);
    }
}
