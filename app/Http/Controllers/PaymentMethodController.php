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
        $paymentMethods = PaymentMethod::ordered()->get();

        return $this->responseList($paymentMethods);
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
        ]);

        $paymentMethod->update($request->only([
            'display_name',
            'icon',
            'max_amount',
            'min_amount',
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

