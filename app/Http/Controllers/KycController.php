<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    /**
     * Get KYC list with filtering and pagination
     * status: pending (待审核), reviewed (已审核), 不传为全部
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'document_number' => 'nullable|string',
            'status' => 'nullable|string|in:pending,reviewed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Kyc::with(['user']);

        // Apply filters
        if ($request->has('document_number') && $request->document_number) {
            $query->byDocumentNumber($request->document_number);
        }

        // 待审核: pending, selfie_pending
        // 已审核: approved, rejected, selfie_approved, selfie_rejected
        if ($request->has('status') && $request->status) {
            if ($request->status === 'pending') {
                $query->whereIn('status', [Kyc::STATUS_PENDING, Kyc::STATUS_SELFIE_PENDING]);
            } elseif ($request->status === 'reviewed') {
                $query->whereIn('status', [
                    Kyc::STATUS_APPROVED,
                    Kyc::STATUS_REJECTED,
                    Kyc::STATUS_SELFIE_APPROVED,
                    Kyc::STATUS_SELFIE_REJECTED,
                ]);
            }
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $kycs = $query->paginate($perPage);

        return $this->responseListWithPaginator($kycs, null);
    }

    /**
     * Approve a KYC request
     * - pending -> approved (初审通过)
     * - selfie_pending -> selfie_approved (自拍审核通过)
     */
    public function approve(Request $request, Kyc $kyc): JsonResponse
    {
        $newStatus = match ($kyc->status) {
            Kyc::STATUS_PENDING => Kyc::STATUS_APPROVED,
            Kyc::STATUS_SELFIE_PENDING => Kyc::STATUS_SELFIE_APPROVED,
            default => null,
        };

        if (!$newStatus) {
            return $this->error(['422', 'Invalid status for approval']);
        }

        $kyc->update([
            'status' => $newStatus,
            'reject_reason' => null,
        ]);

        $kyc->load(['user']);

        return $this->responseItem($kyc);
    }

    /**
     * Reject a KYC request
     * - pending -> rejected (初审拒绝)
     * - selfie_pending -> selfie_rejected (自拍审核拒绝)
     */
    public function reject(Request $request, Kyc $kyc): JsonResponse
    {
        $request->validate([
            'reject_reason' => 'required|string',
        ]);

        $newStatus = match ($kyc->status) {
            Kyc::STATUS_PENDING => Kyc::STATUS_REJECTED,
            Kyc::STATUS_SELFIE_PENDING => Kyc::STATUS_SELFIE_REJECTED,
            default => null,
        };

        if (!$newStatus) {
            return $this->error(['422', 'Invalid status for rejection']);
        }

        $kyc->update([
            'status' => $newStatus,
            'reject_reason' => $request->reject_reason,
        ]);

        $kyc->load(['user']);

        return $this->responseItem($kyc);
    }
}

