<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\CustomerIoCampaignPromotionCode;
use App\Models\PromotionCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerIoCampaignPromotionCodeController extends Controller
{
    /**
     * Paginated links; optional filters campaign_id (exact), promotion_code_id.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => 'nullable|string|max:64',
            'promotion_code_id' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = CustomerIoCampaignPromotionCode::query()->with(['promotionCode']);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', trim((string) $request->input('campaign_id')));
        }

        if ($request->filled('promotion_code_id')) {
            $query->where('promotion_code_id', (int) $request->input('promotion_code_id'));
        }

        $query->orderByDesc('id');

        return $this->responseListWithPaginator($query->paginate($request->get('per_page', 15)), null);
    }

    /**
     * Single link with promotion code loaded.
     */
    public function show(CustomerIoCampaignPromotionCode $customerIoCampaignPromotionCode): JsonResponse
    {
        $customerIoCampaignPromotionCode->load('promotionCode');

        return $this->responseItem($customerIoCampaignPromotionCode);
    }

    /**
     * Create link; campaign_id + promotion_code_id pair must be unique.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|string|max:64',
            'promotion_code_id' => 'required|integer|min:1',
            'remark' => 'nullable|string',
        ]);

        if (! PromotionCode::query()->whereKey($validated['promotion_code_id'])->exists()) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $campaignId = trim($validated['campaign_id']);

        if ($this->duplicatePairExists($campaignId, $validated['promotion_code_id'], null)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $link = CustomerIoCampaignPromotionCode::create([
            'campaign_id' => $campaignId,
            'promotion_code_id' => $validated['promotion_code_id'],
            'remark' => $validated['remark'] ?? null,
        ]);

        return $this->responseItem($link->load('promotionCode'));
    }

    /**
     * Update remark and/or pair; pair must remain unique excluding this row.
     */
    public function update(Request $request, CustomerIoCampaignPromotionCode $customerIoCampaignPromotionCode): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'sometimes|required|string|max:64',
            'promotion_code_id' => 'sometimes|required|integer|min:1',
            'remark' => 'nullable|string',
        ]);

        if (array_key_exists('promotion_code_id', $validated)
            && ! PromotionCode::query()->whereKey($validated['promotion_code_id'])->exists()) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $newCampaignId = array_key_exists('campaign_id', $validated)
            ? trim($validated['campaign_id'])
            : $customerIoCampaignPromotionCode->campaign_id;

        $newPromotionCodeId = array_key_exists('promotion_code_id', $validated)
            ? (int) $validated['promotion_code_id']
            : (int) $customerIoCampaignPromotionCode->promotion_code_id;

        if (
            ($newCampaignId !== $customerIoCampaignPromotionCode->campaign_id
                || $newPromotionCodeId !== (int) $customerIoCampaignPromotionCode->promotion_code_id)
            && $this->duplicatePairExists((string) $newCampaignId, $newPromotionCodeId, $customerIoCampaignPromotionCode->id)
        ) {
            return $this->error(Err::INVALID_PARAMS);
        }

        if (array_key_exists('campaign_id', $validated)) {
            $customerIoCampaignPromotionCode->campaign_id = trim($validated['campaign_id']);
        }

        if (array_key_exists('promotion_code_id', $validated)) {
            $customerIoCampaignPromotionCode->promotion_code_id = $validated['promotion_code_id'];
        }

        if (array_key_exists('remark', $validated)) {
            $customerIoCampaignPromotionCode->remark = $validated['remark'];
        }

        $customerIoCampaignPromotionCode->save();

        return $this->responseItem($customerIoCampaignPromotionCode->fresh()->load('promotionCode'));
    }

    public function destroy(CustomerIoCampaignPromotionCode $customerIoCampaignPromotionCode): JsonResponse
    {
        $customerIoCampaignPromotionCode->delete();

        return $this->responseItem(null);
    }

    private function duplicatePairExists(string $campaignId, int $promotionCodeId, ?int $ignoreId): bool
    {
        $q = CustomerIoCampaignPromotionCode::query()
            ->where('campaign_id', $campaignId)
            ->where('promotion_code_id', $promotionCodeId);

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }
}
