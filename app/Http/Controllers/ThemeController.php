<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    /**
     * Get themes list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|integer',
            'name' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Theme::query();

        // Filter by id if provided
        if ($request->has('id') && $request->id) {
            $query->where('id', $request->id);
        }

        // Filter by name if provided
        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // Filter by enabled status if provided
        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $themes = $query->paginate($perPage);

        return $this->responseListWithPaginator($themes, null);
    }

    /**
     * Get theme details
     */
    public function show(Theme $theme): JsonResponse
    {
        return $this->responseItem($theme);
    }

    /**
     * Create a new theme
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        // Check if theme with same name already exists
        $existingTheme = Theme::where('name', $request->name)->first();
        if ($existingTheme) {
            return $this->error([400, 'Theme with this name already exists']);
        }

        $theme = Theme::create($request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]));

        return $this->responseItem($theme);
    }

    /**
     * Update theme
     */
    public function update(Request $request, Theme $theme): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        // Check name uniqueness if name is being updated
        if ($request->has('name') && $request->name !== $theme->name) {
            $existingTheme = Theme::where('name', $request->name)
                ->where('id', '!=', $theme->id)
                ->first();
            if ($existingTheme) {
                return $this->error([400, 'Theme with this name already exists']);
            }
        }

        $theme->update($request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]));

        return $this->responseItem($theme);
    }
}

