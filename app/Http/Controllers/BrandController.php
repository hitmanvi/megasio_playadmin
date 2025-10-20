<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    /**
     * Get brands list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Brand::with('details');

        // Filter by provider if provided
        if ($request->has('provider') && $request->provider) {
            $query->byProvider($request->provider);
        }

        // Filter by enabled status if provided
        if ($request->has('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $brands = $query->paginate($perPage);

        // Transform the data
        $brands->getCollection()->transform(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'provider' => $brand->provider,
                'restricted_region' => $brand->restricted_region,
                'sort_id' => $brand->sort_id,
                'enabled' => $brand->enabled,
                'maintain_start' => $brand->maintain_start,
                'maintain_end' => $brand->maintain_end,
                'maintain_auto' => $brand->maintain_auto,
                'is_in_maintenance' => $brand->isInMaintenance(),
                'details' => $brand->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'coin' => $detail->coin,
                        'support' => $detail->support,
                        'configured' => $detail->configured,
                        'game_count' => $detail->game_count,
                        'enabled' => $detail->enabled,
                    ];
                }),
                'created_at' => $brand->created_at,
                'updated_at' => $brand->updated_at,
            ];
        });

        return $this->responseListWithPaginator($brands, null);
    }

    /**
     * Get brand details
     */
    public function show(Brand $brand): JsonResponse
    {
        $brand->load('details');

        return $this->responseItem([
            'id' => $brand->id,
            'name' => $brand->name,
            'provider' => $brand->provider,
            'restricted_region' => $brand->restricted_region,
            'sort_id' => $brand->sort_id,
            'enabled' => $brand->enabled,
            'maintain_start' => $brand->maintain_start,
            'maintain_end' => $brand->maintain_end,
            'maintain_auto' => $brand->maintain_auto,
            'is_in_maintenance' => $brand->isInMaintenance(),
            'details' => $brand->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'coin' => $detail->coin,
                    'support' => $detail->support,
                    'configured' => $detail->configured,
                    'game_count' => $detail->game_count,
                    'enabled' => $detail->enabled,
                ];
            }),
            'created_at' => $brand->created_at,
            'updated_at' => $brand->updated_at,
        ]);
    }

    /**
     * Create a new brand
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'restricted_region' => 'nullable|array',
            'restricted_region.*' => 'string',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'maintain_start' => 'nullable|date',
            'maintain_end' => 'nullable|date|after:maintain_start',
            'maintain_auto' => 'nullable|boolean',
        ]);

        $brand = Brand::create($request->all());
        $brand->load('details');

        return $this->responseItem([
            'id' => $brand->id,
            'name' => $brand->name,
            'provider' => $brand->provider,
            'restricted_region' => $brand->restricted_region,
            'sort_id' => $brand->sort_id,
            'enabled' => $brand->enabled,
            'maintain_start' => $brand->maintain_start,
            'maintain_end' => $brand->maintain_end,
            'maintain_auto' => $brand->maintain_auto,
            'is_in_maintenance' => $brand->isInMaintenance(),
            'details' => [],
            'created_at' => $brand->created_at,
            'updated_at' => $brand->updated_at,
        ]);
    }

    /**
     * Update brand
     */
    public function update(Request $request, Brand $brand): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:255',
            'restricted_region' => 'nullable|array',
            'restricted_region.*' => 'string',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'maintain_start' => 'nullable|date',
            'maintain_end' => 'nullable|date|after:maintain_start',
            'maintain_auto' => 'nullable|boolean',
        ]);

        $brand->update($request->all());
        $brand->load('details');

        return $this->responseItem([
            'id' => $brand->id,
            'name' => $brand->name,
            'provider' => $brand->provider,
            'restricted_region' => $brand->restricted_region,
            'sort_id' => $brand->sort_id,
            'enabled' => $brand->enabled,
            'maintain_start' => $brand->maintain_start,
            'maintain_end' => $brand->maintain_end,
            'maintain_auto' => $brand->maintain_auto,
            'is_in_maintenance' => $brand->isInMaintenance(),
            'details' => $brand->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'coin' => $detail->coin,
                    'support' => $detail->support,
                    'configured' => $detail->configured,
                    'game_count' => $detail->game_count,
                    'enabled' => $detail->enabled,
                ];
            }),
            'created_at' => $brand->created_at,
            'updated_at' => $brand->updated_at,
        ]);
    }

    /**
     * Delete brand
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return $this->responseItem([
            'message' => 'Brand deleted successfully'
        ]);
    }

    /**
     * Create brand detail
     */
    public function storeDetail(Request $request, Brand $brand): JsonResponse
    {
        $request->validate([
            'coin' => 'nullable|string|max:255',
            'support' => 'nullable|boolean',
            'configured' => 'nullable|boolean',
            'game_count' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $detail = $brand->details()->create($request->all());

        return $this->responseItem([
            'id' => $detail->id,
            'brand_id' => $detail->brand_id,
            'coin' => $detail->coin,
            'support' => $detail->support,
            'configured' => $detail->configured,
            'game_count' => $detail->game_count,
            'enabled' => $detail->enabled,
            'created_at' => $detail->created_at,
            'updated_at' => $detail->updated_at,
        ]);
    }

    /**
     * Update brand detail
     */
    public function updateDetail(Request $request, Brand $brand, BrandDetail $detail): JsonResponse
    {
        $request->validate([
            'coin' => 'nullable|string|max:255',
            'support' => 'nullable|boolean',
            'configured' => 'nullable|boolean',
            'game_count' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
        ]);

        $detail->update($request->all());

        return $this->responseItem([
            'id' => $detail->id,
            'brand_id' => $detail->brand_id,
            'coin' => $detail->coin,
            'support' => $detail->support,
            'configured' => $detail->configured,
            'game_count' => $detail->game_count,
            'enabled' => $detail->enabled,
            'created_at' => $detail->created_at,
            'updated_at' => $detail->updated_at,
        ]);
    }

    /**
     * Delete brand detail
     */
    public function destroyDetail(Brand $brand, BrandDetail $detail): JsonResponse
    {
        $detail->delete();

        return $this->responseItem([
            'message' => 'Brand detail deleted successfully'
        ]);
    }
}
