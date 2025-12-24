<?php

namespace App\Http\Controllers;

use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\SopayService;
use Carbon\Carbon;
use App\Enums\Err;
use Exception;

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
            'status' => 'nullable|array',
            'status.*' => 'string',
            'approved' => 'nullable|boolean',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Withdraw::with([
            'payment_method',
            'user' => function ($query) {
                $query->with(['firstDeposit', 'kyc'])
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
            $statuses = $request->status;
            if (is_array($statuses) && count($statuses) > 0) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($request->has('approved')) {
            $query->byApproved($request->boolean('approved'));
        }

        if ($request->has('min_amount') && $request->min_amount !== null) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount') && $request->max_amount !== null) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $withdraws = $query->paginate($perPage);

        // Add computed fields - transform to array for adding custom fields
        $items = $withdraws->getCollection()->map(function ($withdraw) {
            $now = Carbon::now();
            $data = $withdraw->toArray();
            
            // Time since first deposit
            if ($withdraw->user && $withdraw->user->firstDeposit && $withdraw->user->firstDeposit->created_at) {
                $data['user_first_deposit_ago'] = Carbon::parse($withdraw->user->firstDeposit->created_at)->diffForHumans($now, true);
            } else {
                $data['user_first_deposit_ago'] = null;
            }
            
            // Time since user registration
            if ($withdraw->user && $withdraw->user->created_at) {
                $data['user_registered_ago'] = Carbon::parse($withdraw->user->created_at)->diffForHumans($now, true);
            } else {
                $data['user_registered_ago'] = null;
            }
            
            // Time taken to complete the order
            if ($withdraw->completed_at && $withdraw->created_at) {
                $data['completion_time'] = Carbon::parse($withdraw->created_at)->diffForHumans(Carbon::parse($withdraw->completed_at), true);
            } else {
                $data['completion_time'] = null;
            }
            
            return $data;
        });
        
        $withdraws->setCollection($items);

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

        DB::beginTransaction();
        try{
            $withdraw->update([
                'approved' => true,
                'status' => Withdraw::STATUS_PROCESSING,
                'note' => $request->note ?? $withdraw->note,
            ]);
    
            $sopayService = new SopayService();
            $resp = $sopayService->withdraw([
                'out_trade_no' => $withdraw->order_no,
                'amount' => $withdraw->actual_amount,
                'symbol' => $withdraw->currency,
                'coin_type' => $withdraw->currency_type,
                'extra_info' => $withdraw->extra_info,
                'user_ip' => $withdraw->user_ip,
            ], [], 2, $withdraw->payment_method?->key);
    
            if (!isset($resp['code']) || $resp['code'] != 0) {
                DB::rollBack();
                return $this->error(Err::SOPAY_ERROR, $resp);
            }
            $withdraw->load(['payment_method', 'user']);
            DB::commit();
            return $this->responseItem($withdraw);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error(Err::UNKNOWN_ERROR, $e->getMessage());
        }
    }

    /**
     * Reject a withdraw request
     */
    public function reject(Request $request, Withdraw $withdraw): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $withdraw) {
            $withdraw->update([
                'approved' => false,
                'status' => Withdraw::STATUS_REJECTED,
                'note' => $request->note ?? $withdraw->note,
            ]);

            $withdraw->load(['payment_method', 'user']);

            return $this->responseItem($withdraw);
        });
    }

    /**
     * Get withdraw counts by status groups
     */
    public function counts(): JsonResponse
    {
        $pendingCount = Withdraw::where('status', Withdraw::STATUS_PENDING)->count();
        $processingCount = Withdraw::where('status', Withdraw::STATUS_PROCESSING)->count();

        return $this->responseItem([
            'pending' => $pendingCount,
            'processing' => $processingCount,
        ]);
    }
}

