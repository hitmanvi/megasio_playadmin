<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Get articles list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string',
            'group_id' => 'nullable',
            'group_id.*' => 'integer',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Article::query();

        // Apply filters
        if ($request->has('title') && $request->title) {
            $query->byTitle($request->title);
        }

        if ($request->has('group_id')) {
            $groupIds = $request->input('group_id');
            // Support both array and single value
            if (is_array($groupIds) && count($groupIds) > 0) {
                $query->whereIn('group_id', $groupIds);
            } elseif (is_numeric($groupIds)) {
                $query->byGroup((int)$groupIds);
            }
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Eager load relationships
        $query->with('group');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $articles = $query->paginate($perPage);

        return $this->responseListWithPaginator($articles, null);
    }

    /**
     * Display the specified article.
     */
    public function show(Article $article): JsonResponse
    {
        $article->load('group');

        return $this->responseItem($article);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'group_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        $article = Article::create($validated);

        return $this->responseItem($article);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(Request $request, Article $article): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'group_id' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
        ]);

        $article->update($validated);

        return $this->responseItem($article);
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
