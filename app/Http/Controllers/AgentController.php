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
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'name' => 'nullable|string',
            'account' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Agent::query();

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->ids);
        }

        if ($request->filled('name')) {
            $query->byName($request->name);
        }

        if ($request->filled('account')) {
            $query->byAccount($request->account);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $query->orderBy('id', 'desc');

        $perPage = $request->get('per_page', 15);
        $agents = $query->withCount('agentLinks')->paginate($perPage);

        return $this->responseListWithPaginator($agents, null);
    }

    /**
     * Display the specified agent.
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->load('agentLinks');

        return $this->responseItem($agent);
    }

    /**
     * Store a newly created agent in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'remark' => 'nullable|string',
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
            'account' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'remark' => 'nullable|string',
            'two_factor_secret' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if (array_key_exists('password', $validated) && empty($validated['password'])) {
            unset($validated['password']);
        }

        $agent->update($validated);

        return $this->responseItem($agent);
    }

    /**
     * Reset agent two_factor_secret (set to null)
     */
    public function resetTwoFactor(Agent $agent): JsonResponse
    {
        $agent->update(['two_factor_secret' => null]);

        return $this->responseItem(['reset' => true]);
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
