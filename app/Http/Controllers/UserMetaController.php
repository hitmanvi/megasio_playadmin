<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\JsonResponse;

class UserMetaController extends Controller
{
    /**
     * User meta rows grouped by key (newest-first value lists per key). Resolved by users.uid.
     */
    public function show(string $uid): JsonResponse
    {
        $user = User::query()->where('uid', $uid)->firstOrFail();

        return $this->responseItem([
            'uid' => $user->uid,
            'user_id' => $user->id,
            'metas' => UserMeta::getAllForUser($user->id),
        ]);
    }
}
