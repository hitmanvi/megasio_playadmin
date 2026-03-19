<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get users list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string',
            'uid' => 'nullable|string',
            'email' => 'nullable|string',
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
            $query->byEmailOrPhone($request->account);
        }

        if ($request->filled('uid')) {
            $query->byUid($request->uid);
        }

        if ($request->filled('email')) {
            $query->byEmail($request->email);
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
                $q->where('id', $request->tag_id);
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

