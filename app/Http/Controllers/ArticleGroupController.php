<?php

namespace App\Http\Controllers;

use App\Models\ArticleGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleGroupController extends Controller
{
    /**
     * Get article groups list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'name' => 'nullable|string',
            'parent_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ArticleGroup::query();

        // Apply filters
        if ($request->has('ids') && is_array($request->ids) && count($request->ids) > 0) {
            $query->whereIn('id', $request->ids);
        }

        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        if ($request->has('parent_id')) {
            $query->byParent($request->integer('parent_id'));
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Eager load relationships
        $query->with(['parent', 'children']);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $articleGroups = $query->paginate($perPage);

        return $this->responseListWithPaginator($articleGroups, null);
    }

    /**
     * Get all article groups as options (for select dropdown)
     */
    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ArticleGroup::query();

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        $query->ordered();

        $query->select(['id', 'name', 'parent_id', 'icon']);

        $perPage = $request->get('per_page', 15);
        $options = $query->paginate($perPage);

        return $this->responseListWithPaginator($options, null);
    }

    /**
     * Display the specified article group.
     */
    public function show(ArticleGroup $articleGroup): JsonResponse
    {
        $articleGroup->load(['parent', 'children', 'articles']);

        return $this->responseItem($articleGroup);
    }

    /**
     * Store a newly created article group in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        $articleGroup = ArticleGroup::create($validated);

        return $this->responseItem($articleGroup);
    }

    /**
     * Update the specified article group in storage.
     */
    public function update(Request $request, ArticleGroup $articleGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        // Prevent setting parent_id to self
        if (isset($validated['parent_id']) && $validated['parent_id'] == $articleGroup->id) {
            return $this->error([1, 'Cannot set parent_id to self']);
        }

        $articleGroup->update($validated);

        return $this->responseItem($articleGroup);
    }

    /**
     * Remove the specified article group from storage.
     */
    public function destroy(ArticleGroup $articleGroup): JsonResponse
    {
        // Check if group has children
        if ($articleGroup->children()->count() > 0) {
            return $this->error([1, 'Cannot delete group with children']);
        }

        // Check if group has articles
        if ($articleGroup->articles()->count() > 0) {
            return $this->error([1, 'Cannot delete group with articles']);
        }

        $articleGroup->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
