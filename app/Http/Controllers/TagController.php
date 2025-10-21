<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Create a new tag with translations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['theme', 'category'])],
            'icon' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'translations' => 'required|array',
            'translations.*' => 'string|max:255',
        ]);

        $tag = Tag::where('name', $validated['name'])->first();
        if ($tag) {
            return $this->error(Err::RECORD_ALREADY_EXISTS);
        }
        $tag = new Tag();
        $tag->name = $validated['name'];
        $tag->type = $validated['type'];
        if (array_key_exists('icon', $validated)) {
            $tag->icon = $validated['icon'];
        }
        if (array_key_exists('enabled', $validated)) {
            $tag->enabled = (bool)$validated['enabled'];
        }
        $tag->save();

        // Set translations
        $tag->setNames($validated['translations']);
        $tag->load('translations');

        // 直接返回tag资源（增加translations虚拟属性），更简洁
        return $this->responseItem($this->withExtraTranslations($tag));
    }

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

        // 将每个tag都增加translations属性
        $tags->getCollection()->transform(function ($tag) {
            return $this->withExtraTranslations($tag);
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
        $tag->load('translations');
        return $this->responseItem($this->withExtraTranslations($tag));
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

        return $this->responseItem($this->withExtraTranslations($tag));
    }

    /**
     * 给tag增加translations属性用于简洁响应
     */
    protected function withExtraTranslations(Tag $tag)
    {
        // 注意：直接设置属性可能会在序列化时被模型已有的 translations 覆盖
        $tag->setRelation('translations', $tag->getAllNames());
        return $tag;
    }
}
