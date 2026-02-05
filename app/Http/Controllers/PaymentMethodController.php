<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PaymentMethod::query();

        // Apply filters
        if ($request->has('ids') && is_array($request->ids) && count($request->ids) > 0) {
            $query->whereIn('id', $request->ids);
        }

        $query->ordered();

        $perPage = $request->get('per_page', 15);
        $paymentMethods = $query->paginate($perPage);

        return $this->responseListWithPaginator($paymentMethods);
    }

    /**
     * Display the specified payment method.
     */
    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return $this->responseItem($paymentMethod);
    }

    /**
     * Store a newly created payment method.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'currency_type' => 'nullable|string|max:255',
            'type' => 'required|string|in:deposit,withdraw',
            'is_fiat' => 'nullable|boolean',
            'max_amount' => 'nullable|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'default_amount' => 'nullable|numeric|min:0',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'crypto_info' => 'nullable|array',
            'fields' => 'nullable|array',
        ]);

        $paymentMethod = PaymentMethod::create($request->only([
            'key',
            'name',
            'display_name',
            'icon',
            'currency',
            'currency_type',
            'type',
            'is_fiat',
            'max_amount',
            'min_amount',
            'default_amount',
            'amounts',
            'sort_id',
            'enabled',
            'notes',
            'crypto_info',
            'fields',
        ]));

        return $this->responseItem($paymentMethod);
    }

    /**
     * Update payment method
     */
    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $request->validate([
            'display_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'max_amount' => 'nullable|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'default_amount' => 'nullable|numeric|min:0',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'sort_id' => 'nullable|integer|min:0',
            'enabled' => 'nullable|boolean',
            'fields' => 'nullable|array',
        ]);

        $paymentMethod->update($request->only([
            'display_name',
            'icon',
            'max_amount',
            'min_amount',
            'default_amount',
            'amounts',
            'sort_id',
            'enabled',
            'fields',
        ]));

        return $this->responseItem($paymentMethod);
    }

    /**
     * Sync payment methods from Sopay service
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            // Run the sync command
            $exitCode = \Illuminate\Support\Facades\Artisan::call('payment-methods:sync');
            
            if ($exitCode === 0) {
                return $this->responseItem([
                    'message' => 'Payment methods synced successfully',
                ]);
            } else {
                return $this->error([500, 'Failed to sync payment methods']);
            }
        } catch (\Exception $e) {
            Log::error('Sync payment methods API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error([500, 'Failed to sync payment methods: ' . $e->getMessage()]);
        }
    }
}

