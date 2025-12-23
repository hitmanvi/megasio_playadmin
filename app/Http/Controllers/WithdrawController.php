<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SopayService;

class WithdrawController extends Controller
{
    /**
     * Get withdraws list with filtering and pagination
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
            'approved' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Withdraw::with([
            'paymentMethod',
            'user' => function ($query) {
                $query->with('firstDeposit')
                    ->withCount(['disputedDeposits']);
            },
        ]);

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

        if ($request->has('approved')) {
            $query->byApproved($request->boolean('approved'));
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $withdraws = $query->paginate($perPage);

        return $this->responseListWithPaginator($withdraws, null);
    }

    /**
     * Approve a withdraw request
     */
    public function pass(Request $request, Withdraw $withdraw): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        $withdraw->update([
            'approved' => true,
            'status' => Withdraw::STATUS_PROCESSING,
            'note' => $request->note ?? $withdraw->note,
        ]);

        $sopayService = new SopayService();
        $sopayService->withdraw([
            'out_trade_no' => $withdraw->order_no,
            'amount' => $withdraw->actual_amount,
            'currency' => $withdraw->currency,
            'coin_type' => $withdraw->currency_type,
            'extra_info' => $withdraw->extra_info,
        ], [], 2, $withdraw->payment_method->key);

        $withdraw->load(['paymentMethod', 'user']);

        return $this->responseItem($withdraw);
    }

    /**
     * Reject a withdraw request
     */
    public function reject(Request $request, Withdraw $withdraw): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        $withdraw->update([
            'approved' => false,
            'status' => Withdraw::STATUS_REJECTED,
            'note' => $request->note ?? $withdraw->note,
        ]);

        $withdraw->load(['paymentMethod', 'user']);

        return $this->responseItem($withdraw);
    }
}

