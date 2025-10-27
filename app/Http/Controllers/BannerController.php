<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{

    /**
     * Get banners list with various filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Banner::query();

        // Apply filters
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        if ($request->has('enabled')) {
            $query->byEnabled($request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $banners = $query->paginate($perPage);

        return $this->responseListWithPaginator($banners, null);
    }

    /**
     * Display the specified banner.
     */
    public function show(Banner $banner): JsonResponse
    {
        return $this->responseItem($banner);
    }

    /**
     * Store a newly created banner in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'web_img_url' => 'nullable|string|max:255',
            'app_img_url' => 'nullable|string|max:255',
            'web_rule_url' => 'nullable|string|max:255',
            'app_rule_url' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $banner = Banner::create($request->all());

        return $this->responseItem($banner);
    }

    /**
     * Update the specified banner in storage.
     */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'web_img_url' => 'nullable|string|max:255',
            'app_img_url' => 'nullable|string|max:255',
            'web_rule_url' => 'nullable|string|max:255',
            'app_rule_url' => 'nullable|string|max:255',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer|min:0',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        // Update fields if provided
        $updateData = $request->only([
            'type',
            'web_img_url',
            'app_img_url',
            'web_rule_url',
            'app_rule_url',
            'enabled',
            'sort_id',
            'started_at',
            'ended_at',
            'description',
        ]);

        // Remove null values
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        });

        $banner->update($updateData);

        return $this->responseItem($banner);
    }

    /**
     * Remove the specified banner from storage.
     */
    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return $this->responseItem([
            'message' => 'Banner deleted successfully'
        ]);
    }
}

