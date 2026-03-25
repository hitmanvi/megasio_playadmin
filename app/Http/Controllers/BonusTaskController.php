<?php

namespace App\Http\Controllers;

use App\Models\BonusTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class BonusTaskController extends Controller
{
    /** @return list<string> */
    private static function allowedBonusTaskStatuses(): array
    {
        return [
            BonusTask::STATUS_PENDING,
            BonusTask::STATUS_ACTIVE,
            BonusTask::STATUS_COMPLETED,
            BonusTask::STATUS_CLAIMED,
            BonusTask::STATUS_EXPIRED,
            BonusTask::STATUS_CANCELLED,
            BonusTask::STATUS_DEPLETED,
        ];
    }

    /**
     * Paginated list of bonus tasks, optionally filtered by user id.
     */
    public function index(Request $request): JsonResponse
    {
        $allowed = self::allowedBonusTaskStatuses();

        $request->validate([
            'user_id' => 'nullable|integer',
            'status' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($allowed) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $items = is_array($value) ? $value : [$value];
                    foreach ($items as $item) {
                        if (! is_string($item) || ! in_array($item, $allowed, true)) {
                            $fail(__('validation.in', ['attribute' => $attribute]));
                            return;
                        }
                    }
                },
            ],
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = BonusTask::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $raw = $request->input('status');
            $statuses = is_array($raw) ? $raw : [$raw];
            $statuses = array_values(array_unique(array_filter($statuses, fn ($s) => is_string($s) && $s !== '')));
            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }
        }

        $perPage = $request->get('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->responseListWithPaginator($paginator, null);
    }
}
