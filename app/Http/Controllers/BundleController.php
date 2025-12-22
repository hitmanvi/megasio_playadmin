<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BundleController extends Controller
{
    /**
     * Get bundles list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Bundle::query();

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
        $bundles = $query->paginate($perPage);

        return $this->responseListWithPaginator($bundles, null);
    }

    /**
     * Create a new bundle
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'gold_coin' => 'nullable|numeric|min:0',
            'social_coin' => 'nullable|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stock' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer',
        ]);

        $bundle = new Bundle();
        $bundle->name = $validated['name'];
        $bundle->display_name = $validated['display_name'] ?? null;
        $bundle->description = $validated['description'] ?? null;
        $bundle->icon = $validated['icon'] ?? null;
        $bundle->gold_coin = $validated['gold_coin'] ?? 0;
        $bundle->social_coin = $validated['social_coin'] ?? 0;
        $bundle->original_price = $validated['original_price'];
        $bundle->discount_price = $validated['discount_price'] ?? null;
        $bundle->currency = $validated['currency'] ?? 'USD';
        $bundle->stock = $validated['stock'] ?? null;
        $bundle->enabled = $validated['enabled'] ?? true;
        $bundle->sort_id = $validated['sort_id'] ?? 0;
        $bundle->save();

        return $this->responseItem($bundle);
    }

    /**
     * Get bundle details
     */
    public function show(Bundle $bundle): JsonResponse
    {
        return $this->responseItem($bundle);
    }

    /**
     * Update bundle
     */
    public function update(Request $request, Bundle $bundle): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'gold_coin' => 'nullable|numeric|min:0',
            'social_coin' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stock' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'sort_id' => 'nullable|integer',
        ]);

        if (array_key_exists('name', $validated)) {
            $bundle->name = $validated['name'];
        }
        if (array_key_exists('display_name', $validated)) {
            $bundle->display_name = $validated['display_name'];
        }
        if (array_key_exists('description', $validated)) {
            $bundle->description = $validated['description'];
        }
        if (array_key_exists('icon', $validated)) {
            $bundle->icon = $validated['icon'];
        }
        if (array_key_exists('gold_coin', $validated)) {
            $bundle->gold_coin = $validated['gold_coin'];
        }
        if (array_key_exists('social_coin', $validated)) {
            $bundle->social_coin = $validated['social_coin'];
        }
        if (array_key_exists('original_price', $validated)) {
            $bundle->original_price = $validated['original_price'];
        }
        if (array_key_exists('discount_price', $validated)) {
            $bundle->discount_price = $validated['discount_price'];
        }
        if (array_key_exists('currency', $validated)) {
            $bundle->currency = $validated['currency'];
        }
        if (array_key_exists('stock', $validated)) {
            $bundle->stock = $validated['stock'];
        }
        if (array_key_exists('enabled', $validated)) {
            $bundle->enabled = $validated['enabled'];
        }
        if (array_key_exists('sort_id', $validated)) {
            $bundle->sort_id = $validated['sort_id'];
        }

        $bundle->save();

        return $this->responseItem($bundle);
    }

    /**
     * Delete bundle
     */
    public function destroy(Bundle $bundle): JsonResponse
    {
        $bundle->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
