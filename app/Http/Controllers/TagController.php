<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Get tags list with type filter and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'locale' => 'nullable|string',
        ]);

        $query = Tag::with('translations');

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by enabled status if provided
        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tags = $query->paginate($perPage);

        // Transform the data to include translations
        $tags->getCollection()->transform(function ($tag) use ($request) {
            $locale = $request->get('locale', app()->getLocale());
            
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'type' => $tag->type,
                'icon' => $tag->icon,
                'enabled' => $tag->enabled,
                'translations' => $tag->getAllNames(),
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at,
            ];
        });

        return $this->responseListWithPaginator($tags, null);
    }

    /**
     * Get tag details
     */
    public function show(Request $request, Tag $tag): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string',
        ]);

        // Load translations
        $tag->load('translations');

        $locale = $request->get('locale', app()->getLocale());

        return $this->responseItem([
            'id' => $tag->id,
            'name' => $tag->name,
            'type' => $tag->type,
            'icon' => $tag->icon,
            'enabled' => $tag->enabled,
            'translations' => $tag->getAllNames(),
            'created_at' => $tag->created_at,
            'updated_at' => $tag->updated_at,
        ]);
    }

    /**
     * Update tag with translations support
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $request->validate([
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        // Update icon if provided
        if ($request->has('icon')) {
            $tag->icon = $request->icon;
            $tag->save();
        }

        // Update enabled status if provided
        if ($request->has('enabled')) {
            $tag->enabled = $request->boolean('enabled');
            $tag->save();
        }

        // Update translations
        $tag->setNames($request->translations);

        // Reload with translations
        $tag->load('translations');

        return $this->responseItem([
            'id' => $tag->id,
            'name' => $tag->name,
            'type' => $tag->type,
            'icon' => $tag->icon,
            'enabled' => $tag->enabled,
            'translations' => $tag->getAllNames(),
            'created_at' => $tag->created_at,
            'updated_at' => $tag->updated_at,
        ]);
    }
}
