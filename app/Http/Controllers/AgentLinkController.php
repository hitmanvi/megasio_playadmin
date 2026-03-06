<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Agent;
use App\Models\AgentLink;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentLinkController extends Controller
{
    /**
     * Get agent links list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'agent_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'promotion_code' => 'nullable|string|max:32',
            'status' => 'nullable|string|in:active,inactive',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AgentLink::query();

        if ($request->filled('agent_id')) {
            $query->byAgentId($request->agent_id);
        }

        if ($request->filled('name')) {
            $query->byName($request->name);
        }

        if ($request->filled('promotion_code')) {
            $query->byPromotionCode($request->promotion_code);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $query->orderBy('id', 'desc');

        $perPage = $request->get('per_page', 15);
        $links = $query->paginate($perPage);

        return $this->responseListWithPaginator($links, null);
    }

    /**
     * Display the specified agent link.
     */
    public function show(AgentLink $agentLink): JsonResponse
    {
        $agentLink->load('agent');

        return $this->responseItem($agentLink);
    }

    /**
     * Store a newly created agent link in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'promotion_code' => 'required|string|max:32',
            'status' => 'nullable|string|in:active,inactive',
            'facebook_config' => 'nullable|array',
            'kochava_config' => 'nullable|array',
        ]);

        $agent = Agent::find($validated['agent_id']);
        if (!$agent) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $link = AgentLink::create($validated);

        return $this->responseItem($link);
    }

    /**
     * Update the specified agent link in storage.
     */
    public function update(Request $request, AgentLink $agentLink): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'promotion_code' => 'nullable|string|max:32',
            'status' => 'nullable|string|in:active,inactive',
            'facebook_config' => 'nullable|array',
            'kochava_config' => 'nullable|array',
        ]);

        $agentLink->update($validated);

        return $this->responseItem($agentLink);
    }

    /**
     * Remove the specified agent link from storage.
     */
    public function destroy(AgentLink $agentLink): JsonResponse
    {
        $agentLink->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
