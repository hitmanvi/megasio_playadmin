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

        // 直接load出来就好了
        // 返回brands及其details，无需二次transform
        return $this->responseListWithPaginator($brands, null);
    }

    /**
     * Get brand details
     */
    public function show(Brand $brand): JsonResponse
    {
        // 直接load details出来，无需额外结构
        $brand->load('details');

        return $this->responseItem($brand);
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

        return $this->responseItem($brand);
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

        return $this->responseItem($brand);
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

        return $this->responseItem($detail);
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

        return $this->responseItem($detail);
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

    public function options(Request $request)
    {
        $options = [
            'coin' => [],
            'language' => [],
            'region' => [],
        ];

        $type = $request->input('type');

        return $this->responseItem($options[$type] ?? []);
    }
}
