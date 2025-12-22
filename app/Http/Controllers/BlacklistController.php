<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlacklistController extends Controller
{
    /**
     * Get blacklist with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'value' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Blacklist::query();

        // Filter by value
        if ($request->has('value') && $request->value) {
            $query->byValue($request->value);
        }

        // Order by created_at desc
        $query->orderByDesc('created_at');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $blacklists = $query->paginate($perPage);

        return $this->responseListWithPaginator($blacklists, null);
    }

    /**
     * Add to blacklist
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $blacklist = new Blacklist();
        $blacklist->value = $validated['value'];
        $blacklist->reason = $validated['reason'] ?? null;
        $blacklist->hit_count = 0;
        $blacklist->save();

        return $this->responseItem($blacklist);
    }

    /**
     * Delete from blacklist
     */
    public function destroy(Blacklist $blacklist): JsonResponse
    {
        $blacklist->delete();

        return $this->responseItem(['deleted' => true]);
    }
}
