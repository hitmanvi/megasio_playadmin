<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserActivityController extends Controller
{
    /**
     * Get user activities list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'uid' => 'nullable|string',
            'account' => 'nullable|string',
            'activity_type' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = UserActivity::with('user');

        // Apply filters
        if ($request->has('user_id') && $request->user_id) {
            $query->byUserId($request->user_id);
        }

        if ($request->has('uid') && $request->uid) {
            $query->byUid($request->uid);
        }

        if ($request->has('account') && $request->account) {
            $query->byUserEmailOrPhone($request->account);
        }

        if ($request->has('activity_type') && $request->activity_type) {
            $query->byActivityType($request->activity_type);
        }

        if ($request->has('ip_address') && $request->ip_address) {
            $query->byIpAddress($request->ip_address);
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $activities = $query->paginate($perPage);

        return $this->responseListWithPaginator($activities, null);
    }
}

