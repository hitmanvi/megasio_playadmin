<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationReward;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

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
            'rewards_by_source_type' => $rewardsBySourceType === [] ? new \stdClass() : $rewardsBySourceType,
        ]);
    }

    /**
     * Paginated invitation rows for a user as inviter, each with invitation_rewards summed by source_type.
     */
    public function aggregatesByInvitation(Request $request, string $uid): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'account' => 'nullable|string',
            'status' => [
                'nullable',
                'string',
                Rule::in([User::STATUS_ACTIVE, User::STATUS_BANNED]),
            ],
        ]);

        $user = User::query()->where('uid', $uid)->firstOrFail();
        $perPage = (int) $request->get('per_page', 15);

        $query = Invitation::query()
            ->where('inviter_id', $user->id);

        if ($request->filled('account') || $request->filled('status')) {
            $query->whereHas('invitee', function ($q) use ($request) {
                if ($request->filled('account')) {
                    $q->byAccount(trim((string) $request->account));
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->string('status'));
                }
            });
        }

        $paginator = $query
            ->with('invitee')
            ->orderByDesc('id')
            ->paginate($perPage);

        $invitationIds = $paginator->getCollection()->pluck('id')->all();

        $byInvitationAndSource = [];
        if ($invitationIds !== []) {
            $rows = InvitationReward::query()
                ->whereIn('invitation_id', $invitationIds)
                ->selectRaw('invitation_id, source_type, SUM(reward_amount) as total')
                ->groupBy('invitation_id', 'source_type')
                ->get();

            foreach ($rows as $row) {
                $byInvitationAndSource[$row->invitation_id][$row->source_type] = (string) $row->total;
            }
        }

        $items = $paginator->getCollection()->map(function (Invitation $invitation) use ($byInvitationAndSource) {
            $map = $byInvitationAndSource[$invitation->id] ?? [];

            return [
                'invitation_id' => $invitation->id,
                'inviter_id' => $invitation->inviter_id,
                'invitee_id' => $invitation->invitee_id,
                'invitation_status' => $invitation->status,
                'total_reward' => (string) $invitation->total_reward,
                'created_at' => $invitation->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $invitation->updated_at?->format('Y-m-d H:i:s'),
                'invitee' => $invitation->invitee
                    ? Arr::only($invitation->invitee->toArray(), ['id', 'uid', 'name', 'email', 'phone', 'status'])
                    : null,
                'rewards_by_source_type' => empty($map) ? new \stdClass() : $map,
            ];
        });

        $paginator->setCollection($items);

        return $this->responseListWithPaginator($paginator, null);
    }
}
