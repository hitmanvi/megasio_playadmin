<?php

namespace App\Http\Controllers;

use App\Models\UserTagLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserTagLogController extends Controller
{
    /**
     * Get user tag logs list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'value' => 'nullable|string',
            'tag_id' => 'nullable|integer',
            'user_status' => 'nullable|string|in:active,banned',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = UserTagLog::with(['user', 'tag']);

        // Filter by value
        if ($request->has('value') && $request->value) {
            $query->byValue($request->value);
        }

        // Filter by tag_id
        if ($request->has('tag_id') && $request->tag_id) {
            $query->byTag($request->tag_id);
        }

        // Filter by user status
        if ($request->has('user_status') && $request->user_status) {
            $query->byUserStatus($request->user_status);
        }

        // Order by created_at desc
        $query->orderByDesc('created_at');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        return $this->responseListWithPaginator($logs, null);
    }
}
