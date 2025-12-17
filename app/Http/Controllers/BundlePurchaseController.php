<?php

namespace App\Http\Controllers;

use App\Models\BundlePurchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BundlePurchaseController extends Controller
{
    /**
     * Get bundle purchases list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string',
            'order_no' => 'nullable|string',
            'out_trade_no' => 'nullable|string',
            'bundle_id' => 'nullable|integer',
            'payment_method_id' => 'nullable|integer',
            'pay_status' => 'nullable|string|in:unpaid,paid,refunded',
            'status' => 'nullable|string|in:pending,completed,failed,cancelled,refunded',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = BundlePurchase::with(['user', 'bundle', 'paymentMethod']);

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

        if ($request->has('bundle_id') && $request->bundle_id) {
            $query->byBundle($request->bundle_id);
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

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $purchases = $query->paginate($perPage);

        return $this->responseListWithPaginator($purchases, null);
    }
}
