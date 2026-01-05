<?php

namespace App\Http\Controllers;

use App\Models\VipLevel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VipLevelController extends Controller
{
    /**
     * Get VIP levels list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = VipLevel::query();

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $query->ordered();

        $perPage = $request->get('per_page', 15);
        $vipLevels = $query->paginate($perPage);

        return $this->responseListWithPaginator($vipLevels, null);
    }

    /**
     * Store a newly created VIP level
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => 'required|string|max:255|unique:megasio_play_api.vip_levels,level',
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'required_exp' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'benefits' => 'nullable|array',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $vipLevel = VipLevel::create($validated);

        return $this->responseItem($vipLevel);
    }

    /**
     * Display the specified VIP level
     */
    public function show(VipLevel $vipLevel): JsonResponse
    {
        return $this->responseItem($vipLevel);
    }

    /**
     * Update the specified VIP level
     */
    public function update(Request $request, VipLevel $vipLevel): JsonResponse
    {
        $validated = $request->validate([
            'level' => 'nullable|string|max:255|unique:megasio_play_api.vip_levels,level,' . $vipLevel->id,
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'required_exp' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'benefits' => 'nullable|array',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $vipLevel->update($validated);

        return $this->responseItem($vipLevel);
    }

    /**
     * Remove the specified VIP level
     */
    public function destroy(VipLevel $vipLevel): JsonResponse
    {
        $vipLevel->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
