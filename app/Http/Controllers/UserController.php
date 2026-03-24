<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    /**
     * User detail for admin (grouped sections).
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['agentLink.agent', 'inviter', 'kyc', 'balances', 'userVip']);

        $firstCompleted = Deposit::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->first(['completed_at']);

        $latestCompleted = Deposit::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->first(['completed_at']);

        $depositTotals = Deposit::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->selectRaw('currency, SUM(COALESCE(actual_amount, amount)) as total')
            ->groupBy('currency')
            ->get();

        $withdrawTotals = Withdraw::query()
            ->where('user_id', $user->id)
            ->where('status', Withdraw::STATUS_COMPLETED)
            ->selectRaw('currency, SUM(COALESCE(actual_amount, amount)) as total')
            ->groupBy('currency')
            ->get();

        $kyc = $user->kyc;

        $data = [
            'activity' => [
                'registered_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'first_deposit_at' => $firstCompleted?->completed_at?->format('Y-m-d H:i:s'),
                'last_active_at' => $user->last_active_at?->format('Y-m-d H:i:s'),
                'latest_deposit_at' => $latestCompleted?->completed_at?->format('Y-m-d H:i:s'),
            ],
            'profile' => [
                'email' => $user->email,
                'phone' => $user->phone,
                'birthdate' => $kyc?->birthdate,
                'kyc' => $kyc ? Arr::only($kyc->toArray(), [
                    'id',
                    'user_id',
                    'name',
                    'birthdate',
                    'document_number',
                    'status',
                    'reject_reason',
                    'document_front',
                    'document_back',
                    'selfie',
                    'created_at',
                    'updated_at',
                ]) : null,
            ],
            'referral' => [
                'agent' => $user->agentLink?->agent,
                'agent_link' => $user->agentLink,
                'inviter' => $this->publicUserSummary($user->inviter),
            ],
            'finance' => [
                'balances' => $user->balances,
                'total_deposit_by_currency' => $depositTotals->map(fn ($row) => [
                    'currency' => $row->currency,
                    'total' => (string) $row->total,
                ])->values()->all(),
                'total_withdraw_by_currency' => $withdrawTotals->map(fn ($row) => [
                    'currency' => $row->currency,
                    'total' => (string) $row->total,
                ])->values()->all(),
            ],
            'vip' => $this->vipDetail($user),
        ];

        return $this->responseItem($data);
    }

    private function publicUserSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return Arr::only($user->toArray(), ['id', 'uid', 'name', 'email', 'phone', 'status']);
    }

    private function vipDetail(User $user): ?array
    {
        $uv = $user->userVip;
        if ($uv === null) {
            return null;
        }

        return [
            'user_vip' => [
                'id' => $uv->id,
                'user_id' => $uv->user_id,
                'level' => $uv->level,
                'exp' => (string) $uv->exp,
                'created_at' => $uv->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $uv->updated_at?->format('Y-m-d H:i:s'),
            ],
            'current_level' => $uv->getCurrentLevelInfo(),
            'benefits' => $uv->getBenefits(),
            'next_level' => $uv->getNextLevelInfo(),
        ];
    }

    /**
     * Get users list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string',
            'agent_id' => 'nullable|integer',
            'agent_link_id' => 'nullable|integer',
            'tag_id' => 'nullable|integer',
            'status' => 'nullable|string|in:active,banned',
            'registered_at_from' => 'nullable|date',
            'registered_at_to' => 'nullable|date|after_or_equal:registered_at_from',
            'balance_min' => 'nullable|numeric',
            'balance_max' => 'nullable|numeric',
            'balance_currency' => 'nullable|string|max:16',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        if ($request->filled('account')) {
            $query->byAccount($request->account);
        }

        if ($request->filled('agent_id')) {
            $query->whereHas('agentLink', function ($q) use ($request) {
                $q->where('agent_id', $request->agent_id);
            });
        }

        if ($request->filled('agent_link_id')) {
            $query->where('agent_link_id', $request->agent_link_id);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('megasio_play_api.tags.id', $request->tag_id);
            });
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('registered_at_from') || $request->filled('registered_at_to')) {
            if ($request->filled('registered_at_from') && $request->filled('registered_at_to')) {
                $query->whereBetween('created_at', [
                    $request->registered_at_from,
                    $request->registered_at_to,
                ]);
            } elseif ($request->filled('registered_at_from')) {
                $query->where('created_at', '>=', $request->registered_at_from);
            } else {
                $query->where('created_at', '<=', $request->registered_at_to);
            }
        }

        if ($request->filled('balance_min') || $request->filled('balance_max')) {
            $balanceSub = \App\Models\Balance::query()
                ->selectRaw('user_id')
                ->groupBy('user_id');
            if ($request->filled('balance_currency')) {
                $balanceSub->where('currency', $request->balance_currency);
            }
            if ($request->filled('balance_min')) {
                $balanceSub->havingRaw('SUM(available + frozen) >= ?', [$request->balance_min]);
            }
            if ($request->filled('balance_max')) {
                $balanceSub->havingRaw('SUM(available + frozen) <= ?', [$request->balance_max]);
            }
            $query->whereIn('id', $balanceSub);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $users = $query->with(['agentLink.agent', 'inviter', 'tags'])->paginate($perPage);

        return $this->responseListWithPaginator($users, null);
    }

    /**
     * Ban a user
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $reason = $request->input('reason');

        // Update user status to banned and save reason
        $user->update([
            'status' => User::STATUS_BANNED,
            'ban_reason' => $reason,
        ]);

        return $this->responseItem($user);
    }

    /**
     * Unban a user
     */
    public function unban(Request $request, User $user): JsonResponse
    {
        // Update user status to active and clear ban reason
        $user->update([
            'status' => User::STATUS_ACTIVE,
            'ban_reason' => null,
        ]);

        return $this->responseItem($user);
    }
}

