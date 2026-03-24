<?php

namespace App\Http\Controllers;

use App\Models\UserPaymentExtraInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPaymentExtraInfoController extends Controller
{
    /**
     * Paginated list of user payment extra_info rows, filtered by user id.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:megasio_play_api.users,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = UserPaymentExtraInfo::query()
            ->where('user_id', $request->integer('user_id'))
            ->orderByDesc('id');

        $perPage = $request->get('per_page', 15);
        $paginator = $query->paginate($perPage);

        return $this->responseListWithPaginator($paginator, null);
    }
}
