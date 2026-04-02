<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Get themes list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'required|integer',
            'name' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Theme::with('translations');

        if ($request->has('ids') && $request->ids) {
            $query->whereIn('id', $request->ids);
        }

        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        $query->ordered();

        $perPage = $request->get('per_page', 15);
        $themes = $query->paginate($perPage);

        $themes->getCollection()->transform(function ($theme) {
            return $this->withExtraTranslations($theme);
        });

        return $this->responseListWithPaginator($themes, null);
    }

    /**
     * Get theme details
     */
    public function show(Theme $theme): JsonResponse
    {
        $theme->load('translations');

        return $this->responseItem($this->withExtraTranslations($theme));
    }

    /**
     * Create a new theme with translations
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'nullable|array',
            'translations.*' => 'string|max:255',
        ]);

        $theme = Theme::create($request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]));

        if ($request->has('translations') && $request->translations) {
            $theme->setNames($request->translations);
        }
        $theme->load('translations');

        return $this->responseItem($this->withExtraTranslations($theme));
    }

    /**
     * Update theme with translations support
     */
    public function update(Request $request, Theme $theme): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        $updateData = $request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]);

        $updateData = array_filter($updateData, fn ($value) => $value !== null);

        if ($updateData !== []) {
            $theme->update($updateData);
        }

        $theme->setNames($request->translations);
        $theme->load('translations');

        return $this->responseItem($this->withExtraTranslations($theme));
    }

    /**
     * Expose name translations as a locale => string map (same shape as GameCategory).
     */
    protected function withExtraTranslations(Theme $theme): Theme
    {
        $theme->setRelation('translations', $theme->getAllNames());

        return $theme;
    }
}
