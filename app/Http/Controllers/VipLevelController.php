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
            'group_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = VipLevel::query();

        if ($request->has('group_id')) {
            $query->byGroup($request->integer('group_id'));
        }

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $query->ordered();

        // Eager load relationships
        $query->with('group');

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
            'group_id' => 'nullable|integer',
            'level' => 'required|string|max:255',
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
     * Batch store VIP levels
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.group_id' => 'nullable|integer',
            'items.*.level' => 'required|string|max:255',
            'items.*.required_exp' => 'nullable|integer|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.benefits' => 'nullable|array',
            'items.*.sort_id' => 'nullable|integer|min:0',
            'items.*.enabled' => 'nullable|boolean',
        ]);

        $created = [];
        foreach ($validated['items'] as $item) {
            $created[] = VipLevel::create($item);
        }

        return $this->responseItem(['created' => $created]);
    }

    /**
     * Display the specified VIP level
     */
    public function show(VipLevel $vipLevel): JsonResponse
    {
        $vipLevel->load('group');
        return $this->responseItem($vipLevel);
    }

    /**
     * Update the specified VIP level
     */
    public function update(Request $request, VipLevel $vipLevel): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => 'nullable|integer',
            'level' => 'nullable|string|max:255',
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
