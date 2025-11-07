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
            'uid' => 'nullable|string',
            'account' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        // Apply filters
        if ($request->has('uid') && $request->uid) {
            $query->byUid($request->uid);
        }

        if ($request->has('account') && $request->account) {
            $query->byEmailOrPhone($request->account);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->responseListWithPaginator($users, null);
    }
}

