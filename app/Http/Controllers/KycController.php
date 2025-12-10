<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    /**
     * Get KYC list with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'document_number' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Kyc::with(['user']);

        // Apply filters
        if ($request->has('document_number') && $request->document_number) {
            $query->byDocumentNumber($request->document_number);
        }

        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
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
     */
    public function approve(Request $request, Kyc $kyc): JsonResponse
    {
        $kyc->update([
            'status' => Kyc::STATUS_APPROVED,
            'reject_reason' => null,
        ]);

        $kyc->load(['user']);

        return $this->responseItem($kyc);
    }

    /**
     * Reject a KYC request
     */
    public function reject(Request $request, Kyc $kyc): JsonResponse
    {
        $request->validate([
            'reject_reason' => 'required|string',
        ]);

        $kyc->update([
            'status' => Kyc::STATUS_REJECTED,
            'reject_reason' => $request->reject_reason,
        ]);

        $kyc->load(['user']);

        return $this->responseItem($kyc);
    }
}

