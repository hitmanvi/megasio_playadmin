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

        // 待审核: pending, advanced_pending, enhanced_pending
        // 已审核: approved, rejected, advanced_approved, advanced_rejected, enhanced_approved, enhanced_rejected
        if ($request->has('status') && $request->status) {
            if ($request->status === 'pending') {
                $query->whereIn('status', [
                    Kyc::STATUS_PENDING,
                    Kyc::STATUS_ADVANCED_PENDING,
                    Kyc::STATUS_ENHANCED_PENDING,
                ]);
            } elseif ($request->status === 'reviewed') {
                $query->whereIn('status', [
                    Kyc::STATUS_APPROVED,
                    Kyc::STATUS_REJECTED,
                    Kyc::STATUS_ADVANCED_APPROVED,
                    Kyc::STATUS_ADVANCED_REJECTED,
                    Kyc::STATUS_ENHANCED_APPROVED,
                    Kyc::STATUS_ENHANCED_REJECTED,
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
     * - pending/rejected -> approved (初审通过，可多次修改)
     * - advanced_pending/advanced_rejected -> advanced_approved (高级认证通过，可多次修改)
     * - enhanced_pending/enhanced_rejected -> enhanced_approved (增强认证通过，可多次修改)
     */
    public function approve(Request $request, Kyc $kyc): JsonResponse
    {
        // Allow "approve" operation not only from pending, but also from rejected states at each level
        $newStatus = match ($kyc->status) {
            Kyc::STATUS_PENDING, Kyc::STATUS_REJECTED => Kyc::STATUS_APPROVED,
            Kyc::STATUS_ADVANCED_PENDING, Kyc::STATUS_ADVANCED_REJECTED => Kyc::STATUS_ADVANCED_APPROVED,
            Kyc::STATUS_ENHANCED_PENDING, Kyc::STATUS_ENHANCED_REJECTED => Kyc::STATUS_ENHANCED_APPROVED,
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
     * - pending/approved -> rejected (初审拒绝，可多次修改)
     * - advanced_pending/advanced_approved -> advanced_rejected (高级认证拒绝，可多次修改)
     * - enhanced_pending/enhanced_approved -> enhanced_rejected (增强认证拒绝，可多次修改)
     */
    public function reject(Request $request, Kyc $kyc): JsonResponse
    {
        $request->validate([
            'reject_reason' => 'required|string',
        ]);

        // Allow "reject" operation not only from pending, but also from approved states at each level
        $newStatus = match ($kyc->status) {
            Kyc::STATUS_PENDING, Kyc::STATUS_APPROVED => Kyc::STATUS_REJECTED,
            Kyc::STATUS_ADVANCED_PENDING, Kyc::STATUS_ADVANCED_APPROVED => Kyc::STATUS_ADVANCED_REJECTED,
            Kyc::STATUS_ENHANCED_PENDING, Kyc::STATUS_ENHANCED_APPROVED => Kyc::STATUS_ENHANCED_REJECTED,
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

