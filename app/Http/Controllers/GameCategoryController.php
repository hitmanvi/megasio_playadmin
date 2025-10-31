<?php

namespace App\Http\Controllers;

use App\Models\GameCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GameCategoryController extends Controller
{
    /**
     * Get game categories list with filtering and pagination
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

        $query = GameCategory::with('translations');

        // Filter by ids if provided
        if ($request->has('ids') && $request->ids) {
            $query->whereIn('id', $request->ids);
        }

        // Filter by name if provided
        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        // Filter by enabled status if provided
        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $categories = $query->paginate($perPage);

        // Transform to add translations and name
        $categories->getCollection()->transform(function ($category) {
            return $this->withExtraTranslations($category);
        });

        return $this->responseListWithPaginator($categories, null);
    }

    /**
     * Get game category details
     */
    public function show(GameCategory $gameCategory): JsonResponse
    {
        $gameCategory->load('translations');
        return $this->responseItem($this->withExtraTranslations($gameCategory));
    }

    /**
     * Create a new game category with translations
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        $category = GameCategory::create($request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]));

        // Set translations
        $category->setNames($request->translations);
        $category->load('translations');

        return $this->responseItem($this->withExtraTranslations($category));
    }

    /**
     * Update game category with translations support
     */
    public function update(Request $request, GameCategory $gameCategory): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        // Update fields if provided
        $updateData = $request->only([
            'name',
            'icon',
            'enabled',
            'sort_id',
        ]);

        // Remove null values
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        });

        if (!empty($updateData)) {
            $gameCategory->update($updateData);
        }

        // Update translations
        $gameCategory->setNames($request->translations);

        // Reload with translations
        $gameCategory->load('translations');

        return $this->responseItem($this->withExtraTranslations($gameCategory));
    }

    /**
     * Add translations attribute and name for cleaner response
     */
    protected function withExtraTranslations(GameCategory $category)
    {
        // Set name attribute (from database field, translated name is in translations)
        // If name field exists in database, it will be automatically included
        // Set translations relation
        $category->setRelation('translations', $category->getAllNames());
        return $category;
    }
}

