<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    /**
     * Get currencies list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Currency::query();

        // Apply filters
        if ($request->has('code') && $request->code) {
            $query->byCode($request->code);
        }

        // Order by sort_id
        $query->ordered();

        // Pagination
        $perPage = $request->get('per_page', 15);
        $currencies = $query->paginate($perPage);

        return $this->responseListWithPaginator($currencies, null);
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency): JsonResponse
    {
        return $this->responseItem($currency);
    }

    /**
     * Store a newly created currency in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'symbol' => 'required|string|max:10',
            'icon' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'type' => 'string|max:255',
            'name' => 'string|max:255',
            'enabled' => 'nullable|boolean',
        ]);

        $currency = Currency::create($request->all());

        return $this->responseItem($currency);
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(Request $request, Currency $currency): JsonResponse
    {
        $request->validate([
            'code' => 'nullable|string|max:10',
            'symbol' => 'nullable|string|max:10',
            'icon' => 'nullable|string|max:255',
            'sort_id' => 'nullable|integer|min:0',
            'name' => 'string|max:255',
            'type' => 'string|max:255',
            'enabled' => 'nullable|boolean',
        ]);

        // Update fields if provided
        $updateData = $request->only([
            'code',
            'symbol',
            'icon',
            'name',
            'sort_id',
            'type',
            'enabled',
        ]);

        // Remove null values
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        });

        $currency->update($updateData);

        return $this->responseItem($currency);
    }

    /**
     * Remove the specified currency from storage.
     */
    public function destroy(Currency $currency): JsonResponse
    {
        $currency->delete();

        return $this->responseItem([
            'message' => 'Currency deleted successfully'
        ]);
    }
}

