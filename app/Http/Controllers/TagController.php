<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * Get tags list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Tag::query();

        // Filter by name
        if ($request->has('name') && $request->name) {
            $query->byName($request->name);
        }

        // Filter by enabled status
        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // Order by sort_id asc, id desc
        $query->orderBy('sort_id')->orderByDesc('id');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tags = $query->paginate($perPage);

        return $this->responseListWithPaginator($tags, null);
    }

    /**
     * Create a new tag
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:megasio_play_api.tags,name',
            'display_name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer',
        ]);

        $tag = new Tag();
        $tag->name = $validated['name'];
        $tag->display_name = $validated['display_name'] ?? null;
        $tag->color = $validated['color'] ?? null;
        $tag->description = $validated['description'] ?? null;
        $tag->enabled = $validated['enabled'] ?? true;
        $tag->sort_id = $validated['sort_id'] ?? 0;
        $tag->save();

        return $this->responseItem($tag);
    }

    /**
     * Get tag details
     */
    public function show(Tag $tag): JsonResponse
    {
        return $this->responseItem($tag);
    }

    /**
     * Update tag
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:megasio_play_api.tags,name,' . $tag->id,
            'display_name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer',
        ]);

        if (array_key_exists('name', $validated)) {
            $tag->name = $validated['name'];
        }
        if (array_key_exists('display_name', $validated)) {
            $tag->display_name = $validated['display_name'];
        }
        if (array_key_exists('color', $validated)) {
            $tag->color = $validated['color'];
        }
        if (array_key_exists('description', $validated)) {
            $tag->description = $validated['description'];
        }
        if (array_key_exists('enabled', $validated)) {
            $tag->enabled = $validated['enabled'];
        }
        if (array_key_exists('sort_id', $validated)) {
            $tag->sort_id = $validated['sort_id'];
        }

        $tag->save();

        return $this->responseItem($tag);
    }

    /**
     * Delete tag
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return $this->responseItem(['deleted' => true]);
    }

    /**
     * Attach tags to a user
     */
    public function attachToUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:megasio_play_api.tags,id',
        ]);

        $user->tags()->syncWithoutDetaching($validated['tag_ids']);

        $user->load('tags');

        return $this->responseItem($user);
    }

    /**
     * Detach tags from a user
     */
    public function detachFromUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:megasio_play_api.tags,id',
        ]);

        $user->tags()->detach($validated['tag_ids']);

        $user->load('tags');

        return $this->responseItem($user);
    }

    /**
     * Sync tags for a user (replace all)
     */
    public function syncUserTags(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:megasio_play_api.tags,id',
        ]);

        $user->tags()->sync($validated['tag_ids']);

        $user->load('tags');

        return $this->responseItem($user);
    }

    /**
     * Get users by tag
     */
    public function getUsers(Request $request, Tag $tag): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->get('per_page', 15);
        $users = $tag->users()->paginate($perPage);

        return $this->responseListWithPaginator($users, null);
    }
}
