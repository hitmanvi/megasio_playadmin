<?php

namespace App\Http\Controllers;

use App\Models\VipLevelGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VipLevelGroupController extends Controller
{
    /**
     * Get VIP level groups list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = VipLevelGroup::query();

        // Apply filters
        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Eager load relationships
        $query->with('vipLevels');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $groups = $query->paginate($perPage);

        return $this->responseListWithPaginator($groups, null);
    }

    /**
     * Display the specified VIP level group.
     */
    public function show(VipLevelGroup $vipLevelGroup): JsonResponse
    {
        $vipLevelGroup->load('vipLevels');
        return $this->responseItem($vipLevelGroup);
    }

    /**
     * Store a newly created VIP level group in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'card_img' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $group = VipLevelGroup::create($validated);

        return $this->responseItem($group);
    }

    /**
     * Update the specified VIP level group in storage.
     */
    public function update(Request $request, VipLevelGroup $vipLevelGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'card_img' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $vipLevelGroup->update($validated);

        return $this->responseItem($vipLevelGroup);
    }

    /**
     * Remove the specified VIP level group from storage.
     */
    public function destroy(VipLevelGroup $vipLevelGroup): JsonResponse
    {
        // Check if group has VIP levels
        if ($vipLevelGroup->vipLevels()->count() > 0) {
            return $this->error([1, 'Cannot delete group with VIP levels']);
        }

        $vipLevelGroup->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
