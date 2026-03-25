<?php

namespace App\Http\Controllers;

use App\Models\BonusTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BonusTaskController extends Controller
{
    /**
     * Paginated list of bonus tasks, optionally filtered by user id.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    BonusTask::STATUS_PENDING,
                    BonusTask::STATUS_ACTIVE,
                    BonusTask::STATUS_COMPLETED,
                    BonusTask::STATUS_CLAIMED,
                    BonusTask::STATUS_EXPIRED,
                    BonusTask::STATUS_CANCELLED,
                    BonusTask::STATUS_DEPLETED,
                ]),
            ],
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = BonusTask::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = $request->get('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->responseListWithPaginator($paginator, null);
    }
}
