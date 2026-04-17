<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\PromotionCodeClaim;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionCodeClaimController extends Controller
{
    /**
     * Paginated promotion code claims; filter by account (users.uid OR users.email, exact), status, or promotion_code_id.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'nullable|string|max:255',
            'status' => ['nullable', 'string', Rule::in(PromotionCodeClaim::statuses())],
            'promotion_code_id' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PromotionCodeClaim::query()->with(['user', 'promotionCode']);

        if ($request->filled('account')) {
            $account = trim((string) $request->input('account'));
            $user = User::query()
                ->where(function ($q) use ($account) {
                    $q->where('uid', $account)->orWhere('email', $account);
                })
                ->first();

            if ($user === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('user_id', $user->id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('promotion_code_id')) {
            $query->where('promotion_code_id', (int) $request->input('promotion_code_id'));
        }

        $query->orderByDesc('id');

        $perPage = $request->get('per_page', 15);
        $items = $query->paginate($perPage);

        return $this->responseListWithPaginator($items, null);
    }

    /**
     * Delete a claim; only allowed when status is pending.
     */
    public function destroy(PromotionCodeClaim $promotionCodeClaim): JsonResponse
    {
        if ($promotionCodeClaim->status !== PromotionCodeClaim::STATUS_PENDING) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $promotionCodeClaim->delete();

        return $this->responseItem(null);
    }
}
