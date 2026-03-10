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
            'promotion_code' => 'nullable|string|size:4|alpha',
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
     * promotion_code 由服务端自动生成（4 位唯一）。
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'facebook_config' => 'nullable|array',
            'kochava_config' => 'nullable|array',
        ]);

        $agent = Agent::find($validated['agent_id']);
        if (!$agent) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $validated['promotion_code'] = $this->generateUniquePromotionCode();

        $link = AgentLink::create($validated);

        return $this->responseItem($link);
    }

    /**
     * 生成 4 位唯一推广码（数字+大写字母，排除易混淆字符）
     */
    private function generateUniquePromotionCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // 纯字母，排除 I/O 易混淆
        $maxAttempts = 100;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = '';
            for ($j = 0; $j < 4; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if (!AgentLink::where('promotion_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate unique promotion code.');
    }

    /**
     * Update the specified agent link in storage.
     */
    public function update(Request $request, AgentLink $agentLink): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'promotion_code' => 'nullable|string|size:4|alpha',
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
