<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * List of transaction type values (for filters / UI).
     */
    public function types(): JsonResponse
    {
        return $this->responseItem([
            'types' => Transaction::allTypes(),
        ]);
    }

    /**
     * Paginated transactions with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'currency' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:64',
            'status' => 'nullable|string|max:32',
            'transaction_time_from' => 'nullable|date',
            'transaction_time_to' => 'nullable|date|after_or_equal:transaction_time_from',
            'sort_by' => 'nullable|string|in:id,transaction_time,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Transaction::query()->with(['user:id,uid,email,phone,name']);

        if ($request->filled('user_id')) {
            $query->forUser($request->integer('user_id'));
        }
        if ($request->filled('currency')) {
            $query->forCurrency(trim((string) $request->currency));
        }
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }
        if ($request->filled('transaction_time_from') && $request->filled('transaction_time_to')) {
            $query->inDateRange($request->transaction_time_from, $request->transaction_time_to);
        } elseif ($request->filled('transaction_time_from')) {
            $query->where('transaction_time', '>=', $request->transaction_time_from);
        } elseif ($request->filled('transaction_time_to')) {
            $query->where('transaction_time', '<=', $request->transaction_time_to);
        }

        $sortBy = $request->get('sort_by', 'transaction_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder)->orderBy('id', $sortOrder);

        $paginator = $query->paginate($request->get('per_page', 15));

        return $this->responseListWithPaginator($paginator, null);
    }
}
