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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'locale' => 'nullable|string',
        ]);

        $query = Tag::with('translations');

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tags = $query->paginate($perPage);

        // Transform the data to include translations
        $tags->getCollection()->transform(function ($tag) use ($request) {
            $locale = $request->get('locale', app()->getLocale());
            
            return [
                'id' => $tag->id,
                'name' => $tag->getName($locale),
                'type' => $tag->type,
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
            'name' => $tag->getName($locale),
            'type' => $tag->type,
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
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        // Update translations only
        $tag->setNames($request->translations);

        // Reload with translations
        $tag->load('translations');

        return $this->responseItem([
            'id' => $tag->id,
            'name' => $tag->name,
            'type' => $tag->type,
            'translations' => $tag->getAllNames(),
            'created_at' => $tag->created_at,
            'updated_at' => $tag->updated_at,
        ]);
    }
}
