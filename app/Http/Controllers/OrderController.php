<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Get orders list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'nullable|string',
            'out_id' => 'nullable|string',
            'game_name' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'min_amount' => 'nullable|numeric',
            'max_amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Order::with(['game', 'brand']);

        // Apply filters
        if ($request->has('order_id') && $request->order_id) {
            $query->byOrderId($request->order_id);
        }

        if ($request->has('out_id') && $request->out_id) {
            $query->byOutId($request->out_id);
        }

        if ($request->has('game_name') && $request->game_name) {
            $query->byGameName($request->game_name);
        }

        if ($request->has('currency') && $request->currency) {
            $query->byCurrency($request->currency);
        }

        if ($request->has('min_amount') || $request->has('max_amount')) {
            $query->byAmountRange($request->get('min_amount'), $request->get('max_amount'));
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $query->byDateRange($request->get('start_date'), $request->get('end_date'));
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return $this->responseListWithPaginator($orders, null);
    }
}

