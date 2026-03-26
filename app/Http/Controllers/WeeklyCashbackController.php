<?php

namespace App\Http\Controllers;

use App\Models\WeeklyCashback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeeklyCashbackController extends Controller
{
    /**
     * Paginated weekly_cashbacks with optional user_id and status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer|min:1',
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    WeeklyCashback::STATUS_ACTIVE,
                    WeeklyCashback::STATUS_CLAIMABLE,
                    WeeklyCashback::STATUS_CLAIMED,
                    WeeklyCashback::STATUS_EXPIRED,
                ]),
            ],
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = WeeklyCashback::query()
            ->with(['user:id,uid,email,phone,name'])
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return $this->responseListWithPaginator(
            $query->paginate($request->get('per_page', 15)),
            null
        );
    }

    /**
     * Per-currency sum of amount for rows with status=claimed (optional user_id scope).
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer|min:1',
        ]);

        $query = WeeklyCashback::query()
            ->where('status', WeeklyCashback::STATUS_CLAIMED);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $rows = (clone $query)
            ->selectRaw('currency, SUM(COALESCE(amount, 0)) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $byCurrency = $rows->map(fn ($row) => [
            'currency' => $row->currency ?? '',
            'total' => (string) $row->total,
        ])->values()->all();

        $data = [
            'total_claimed_by_currency' => $byCurrency,
        ];
        if ($request->filled('user_id')) {
            $data['user_id'] = $request->integer('user_id');
        }

        return $this->responseItem($data);
    }
}
