<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethodFieldConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodFieldConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PaymentMethodFieldConfig::query()->orderByDesc('id');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        return $this->responseListWithPaginator(
            $query->paginate($request->get('per_page', 15)),
            null
        );
    }

    public function show(PaymentMethodFieldConfig $paymentMethodFieldConfig): JsonResponse
    {
        return $this->responseItem($paymentMethodFieldConfig);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'deposit_fields' => 'nullable|array',
            'withdraw_fields' => 'nullable|array',
        ]);

        $row = PaymentMethodFieldConfig::create($request->only([
            'name',
            'deposit_fields',
            'withdraw_fields',
        ]));

        return $this->responseItem($row);
    }

    public function update(Request $request, PaymentMethodFieldConfig $paymentMethodFieldConfig): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'deposit_fields' => 'nullable|array',
            'withdraw_fields' => 'nullable|array',
        ]);

        $paymentMethodFieldConfig->update($request->only([
            'name',
            'deposit_fields',
            'withdraw_fields',
        ]));

        return $this->responseItem($paymentMethodFieldConfig->fresh());
    }

    public function destroy(PaymentMethodFieldConfig $paymentMethodFieldConfig): JsonResponse
    {
        $paymentMethodFieldConfig->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
