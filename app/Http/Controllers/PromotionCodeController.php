<?php

namespace App\Http\Controllers;

use App\Models\PromotionCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionCodeController extends Controller
{
    /**
     * Filter option lists: bonus_type values and status filter values (string arrays, no labels).
     */
    public function options(): JsonResponse
    {
        return $this->responseItem([
            'type' => PromotionCode::bonusTypes(),
            'status' => PromotionCode::statusFilterValues(),
        ]);
    }

    /**
     * Paginated promotion codes list; filter by name (partial), code (exact), bonus_type, or status (active/inactive/exhausted on column, or expired = past expired_at).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'bonus_type' => ['nullable', 'string', Rule::in(PromotionCode::bonusTypes())],
            'status' => 'nullable|string|in:active,inactive,exhausted,expired',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PromotionCode::query();

        if ($request->filled('name')) {
            $query->nameContains(trim((string) $request->input('name')));
        }

        if ($request->filled('code')) {
            $query->byCode(trim((string) $request->input('code')));
        }

        if ($request->filled('bonus_type')) {
            $query->byBonusType((string) $request->input('bonus_type'));
        }

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'expired') {
                $query->whereGloballyExpired();
            } else {
                $query->byStatus($status);
            }
        }

        $query->orderByDesc('id');

        $perPage = $request->get('per_page', 15);
        $items = $query->paginate($perPage);

        return $this->responseListWithPaginator($items, null);
    }
}
