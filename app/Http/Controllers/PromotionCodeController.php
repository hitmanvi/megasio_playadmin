<?php

namespace App\Http\Controllers;

use App\Models\PromotionCode;
use App\Models\PromotionCodeClaim;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PromotionCodeController extends Controller
{
    /**
     * bonus_type filter values as a plain string array in data (no wrapper object).
     */
    public function typeOptions(): JsonResponse
    {
        return $this->responseItem(PromotionCode::bonusTypes());
    }

    /**
     * Status filter values as a plain string array in data (no wrapper; includes virtual expired).
     */
    public function statusOptions(): JsonResponse
    {
        return $this->responseItem(PromotionCode::statusFilterValues());
    }

    /**
     * Whether promotion_codes.code already exists (exact match after trim). Optional exclude_id ignores that row (e.g. when editing).
     */
    public function codeExists(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'exclude_id' => 'nullable|integer|min:1',
        ]);

        $code = trim($validated['code']);
        $query = PromotionCode::query()->where('code', $code);

        if (! empty($validated['exclude_id'])) {
            $query->where('id', '!=', (int) $validated['exclude_id']);
        }

        return $this->responseItem([
            'exists' => $query->exists(),
        ]);
    }

    /**
     * Single promotion code with claims (newest claims first) and claim users loaded.
     */
    public function show(PromotionCode $promotionCode): JsonResponse
    {
        $promotionCode->load([
            'claims' => static fn ($q) => $q->orderByDesc('id'),
            'claims.user',
        ]);

        return $this->responseItem($promotionCode);
    }

    /**
     * Create a promotion code. claimed_count is always 0; status may be active or inactive (default active).
     * When uids is non-empty and target_type is not all, creates pending promotion_code_claims for those users (by users.uid).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255'],
            'times' => 'required|integer|min:1',
            'bonus_type' => ['required', 'string', Rule::in(PromotionCode::bonusTypes())],
            'bonus_config' => 'nullable|array',
            'expired_at' => 'nullable|date',
            'target_type' => ['required', 'string', Rule::in(PromotionCode::targetTypes())],
            'status' => ['nullable', 'string', Rule::in(PromotionCode::creatableStatuses())],
            'uids' => 'nullable|array|max:5000',
            'uids.*' => 'required|string|max:255',
            'uid_valid_days' => 'nullable|integer|min:1|max:36500',
        ]);

        $uids = $this->uniqueUidsList($validated['uids'] ?? []);

        $uidValidDays = $this->uidValidDaysFromValidated($validated);

        $promotionCode = DB::transaction(function () use ($validated, $uids, $uidValidDays) {
            $promotionCode = PromotionCode::create([
                'name' => $validated['name'],
                'code' => trim($validated['code']),
                'times' => $validated['times'],
                'claimed_count' => 0,
                'bonus_type' => $validated['bonus_type'],
                'bonus_config' => $validated['bonus_config'] ?? [],
                'expired_at' => isset($validated['expired_at']) ? $validated['expired_at'] : null,
                'target_type' => $validated['target_type'],
                'status' => $validated['status'] ?? PromotionCode::STATUS_ACTIVE,
            ]);

            $this->appendPendingClaimsForUids($promotionCode, $uids, $uidValidDays);

            return $promotionCode;
        });

        return $this->responseItem($promotionCode);
    }

    /**
     * Partial update; uids creates pending claims for new users, or refreshes expired_at on existing claims for this code.
     */
    public function update(Request $request, PromotionCode $promotionCode): JsonResponse
    {
        $minTimes = max(1, (int) $promotionCode->claimed_count);

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'string', Rule::in(PromotionCode::updatableStatuses())],
            'times' => ['sometimes', 'required', 'integer', 'min:'.$minTimes],
            'bonus_config' => 'nullable|array',
            'expired_at' => 'nullable|date',
            'uids' => 'nullable|array|max:5000',
            'uids.*' => 'required|string|max:255',
            'uid_valid_days' => 'nullable|integer|min:1|max:36500',
        ]);

        $uids = $this->uniqueUidsList($validated['uids'] ?? []);

        $uidValidDays = $this->uidValidDaysFromValidated($validated);

        DB::transaction(function () use ($validated, $uids, $uidValidDays, $promotionCode) {
            foreach ([
                'status',
                'times',
                'bonus_config',
                'expired_at',
            ] as $key) {
                if (! array_key_exists($key, $validated)) {
                    continue;
                }
                $promotionCode->{$key} = $validated[$key];
            }

            if ($promotionCode->isDirty()) {
                $promotionCode->save();
            }

            $promotionCode->refresh();

            if (count($uids) > 0) {
                $this->appendPendingClaimsForUids($promotionCode, $uids, $uidValidDays);
            }
        });

        return $this->responseItem($promotionCode->fresh());
    }

    /**
     * For resolved users.uid: inserts pending claims for users without a row; if a claim already exists, updates expired_at (and updated_at) to match uid_valid_days.
     *
     * @param  list<string>  $uids
     */
    private function appendPendingClaimsForUids(PromotionCode $promotionCode, array $uids, ?int $uidValidDays = null): void
    {
        if (count($uids) === 0) {
            return;
        }

        if ($promotionCode->target_type === PromotionCode::TARGET_TYPE_ALL) {
            throw ValidationException::withMessages([
                'uids' => ['The uids field must be empty when target_type is all.'],
            ]);
        }

        $users = User::query()->whereIn('uid', $uids)->get();
        if ($users->count() !== count($uids)) {
            $found = $users->pluck('uid')->all();
            $missing = array_values(array_diff($uids, $found));
            throw ValidationException::withMessages([
                'uids' => ['Unknown uids: ' . implode(', ', $missing)],
            ]);
        }

        $existingUserIds = PromotionCodeClaim::query()
            ->where('promotion_code_id', $promotionCode->id)
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('user_id')
            ->all();

        $now = now();
        $claimExpiredAt = $uidValidDays !== null
            ? $now->copy()->addDays($uidValidDays)
            : null;

        $rows = [];
        foreach ($users as $user) {
            if (in_array($user->id, $existingUserIds, true)) {
                continue;
            }
            $rows[] = [
                'user_id' => $user->id,
                'promotion_code_id' => $promotionCode->id,
                'status' => PromotionCodeClaim::STATUS_PENDING,
                'claimed_at' => null,
                'expired_at' => $claimExpiredAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            PromotionCodeClaim::insert($rows);
        }

        $userIdsToRefreshExpiry = array_values(array_intersect(
            $users->pluck('id')->all(),
            $existingUserIds
        ));

        if ($userIdsToRefreshExpiry !== []) {
            PromotionCodeClaim::query()
                ->where('promotion_code_id', $promotionCode->id)
                ->whereIn('user_id', $userIdsToRefreshExpiry)
                ->update([
                    'expired_at' => $claimExpiredAt,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @param  list<string>|null  $uids
     * @return list<string>
     */
    private function uniqueUidsList(?array $uids): array
    {
        if ($uids === null || $uids === []) {
            return [];
        }

        $seen = [];
        foreach ($uids as $raw) {
            $u = trim((string) $raw);
            if ($u !== '') {
                $seen[$u] = true;
            }
        }

        return array_keys($seen);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function uidValidDaysFromValidated(array $validated): ?int
    {
        if (! array_key_exists('uid_valid_days', $validated) || $validated['uid_valid_days'] === null) {
            return null;
        }

        return (int) $validated['uid_valid_days'];
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
