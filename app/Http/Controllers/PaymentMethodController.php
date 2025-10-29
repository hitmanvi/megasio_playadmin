<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
}

