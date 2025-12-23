<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    /**
     * Get deposits list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string',
            'order_no' => 'nullable|string',
            'out_trade_no' => 'nullable|string',
            'payment_method_id' => 'nullable|integer',
            'pay_status' => 'nullable|string',
            'status' => 'nullable|string',
            'is_disputed' => 'nullable|boolean',
            'resolved_status' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Deposit::with(['paymentMethod', 'user.tags']);

        // Apply filters
        if ($request->has('account') && $request->account) {
            $query->byUserEmailOrPhone($request->account);
        }

        if ($request->has('order_no') && $request->order_no) {
            $query->byOrderNo($request->order_no);
        }

        if ($request->has('out_trade_no') && $request->out_trade_no) {
            $query->byOutTradeNo($request->out_trade_no);
        }

        if ($request->has('payment_method_id') && $request->payment_method_id) {
            $query->byPaymentMethod($request->payment_method_id);
        }

        if ($request->has('pay_status') && $request->pay_status) {
            $query->byPayStatus($request->pay_status);
        }

        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        if ($request->has('is_disputed') && $request->is_disputed !== null) {
            $query->byDisputed(filter_var($request->is_disputed, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('resolved_status') && $request->resolved_status) {
            $query->byResolvedStatus($request->resolved_status);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $deposits = $query->paginate($perPage);

        return $this->responseListWithPaginator($deposits, null);
    }

    /**
     * Resolve disputed deposit
     */
    public function resolve(Request $request, Deposit $deposit): JsonResponse
    {
        $validated = $request->validate([
            'resolved_status' => 'nullable|string|max:255',
            'abnormal_status' => 'nullable|string|max:255',
        ]);
        $deposit->is_disputed = true;
        if ($validated['resolved_status'] ?? null) {
            $deposit->resolved_status = $validated['resolved_status'];
        }
        if ($validated['abnormal_status'] ?? null) {
            $deposit->abnormal_status = $validated['abnormal_status'];
            $deposit->abnormal_at = now();
        }

        $deposit->save();

        return $this->responseItem($deposit, 'Deposit resolved successfully');
    }
}
