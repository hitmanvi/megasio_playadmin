<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    /**
     * Get agents list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'promotion_code' => 'nullable|string|max:32',
            'parent_id' => 'nullable|integer',
            'status' => 'nullable|string|in:active,inactive',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Agent::query();

        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        if ($request->has('promotion_code') && $request->promotion_code) {
            $query->byPromotionCode($request->promotion_code);
        }

        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        if ($request->filled('parent_id')) {
            $query->byParentId($request->parent_id);
        }

        $query->orderBy('id', 'desc');

        $perPage = $request->get('per_page', 15);
        $agents = $query->paginate($perPage);

        return $this->responseListWithPaginator($agents, null);
    }

    /**
     * Display the specified agent.
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->load('parent');

        return $this->responseItem($agent);
    }

    /**
     * Store a newly created agent in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'promotion_code' => 'required|string|max:32',
            'parent_id' => 'nullable|integer',
            'facebook_pixel_id' => 'nullable|string|max:255',
            'facebook_access_token' => 'nullable|string',
            'kochava_app_id' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $agent = Agent::create($validated);

        return $this->responseItem($agent);
    }

    /**
     * Update the specified agent in storage.
     */
    public function update(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'promotion_code' => 'nullable|string|max:32|unique:megasio_play_api.agents,promotion_code,' . $agent->id,
            'parent_id' => 'nullable|integer',
            'facebook_pixel_id' => 'nullable|string|max:255',
            'facebook_access_token' => 'nullable|string',
            'kochava_app_id' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $agent->update($validated);

        return $this->responseItem($agent);
    }

    /**
     * Remove the specified agent from storage.
     */
    public function destroy(Agent $agent): JsonResponse
    {
        $agent->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
