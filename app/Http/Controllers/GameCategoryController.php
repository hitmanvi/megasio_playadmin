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
            'id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'locale' => 'nullable|string',
        ]);

        $query = GameCategory::with('translations');

        // Filter by id if provided
        if ($request->has('id') && $request->id) {
            $query->where('id', $request->id);
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

        // Transform to add translations
        $locale = $request->get('locale');
        $categories->getCollection()->transform(function ($category) use ($locale) {
            return $this->withExtraTranslations($category, $locale);
        });

        return $this->responseListWithPaginator($categories, null);
    }

    /**
     * Get game category details
     */
    public function show(Request $request, GameCategory $gameCategory): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string',
        ]);
        
        $gameCategory->load('translations');
        $locale = $request->get('locale');
        return $this->responseItem($this->withExtraTranslations($gameCategory, $locale));
    }

    /**
     * Create a new game category with translations
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        $category = GameCategory::create($request->only([
            'icon',
            'enabled',
            'sort_id',
        ]));

        // Set translations
        $category->setNames($request->translations);
        $category->load('translations');

        $locale = $request->get('locale');
        return $this->responseItem($this->withExtraTranslations($category, $locale));
    }

    /**
     * Update game category with translations support
     */
    public function update(Request $request, GameCategory $gameCategory): JsonResponse
    {
        $request->validate([
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        // Update fields if provided
        $updateData = $request->only([
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

        $locale = $request->get('locale');
        return $this->responseItem($this->withExtraTranslations($gameCategory, $locale));
    }

    /**
     * Add translations attribute and name for cleaner response
     */
    protected function withExtraTranslations(GameCategory $category, ?string $locale = null)
    {
        $category->setRelation('translations', $category->getAllNames());
        return $category;
    }
}

