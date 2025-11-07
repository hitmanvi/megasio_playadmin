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
            'status' => 'nullable|string|in:active,banned',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        // Apply filters
        if ($request->has('account') && $request->account) {
            $query->byEmailOrPhone($request->account);
        }

        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

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

