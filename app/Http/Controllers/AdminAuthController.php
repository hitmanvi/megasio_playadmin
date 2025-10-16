<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Admin login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('name', 'password');

        $admin = Admin::where('name', $credentials['name'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        // 使用Sanctum创建token
        $token = $admin->createToken('admin-token')->plainTextToken;

        return $this->responseItem([
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated admin info
     */
    public function mine(Request $request): JsonResponse
    {
        return $this->responseItem([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
        ]);
    }
}
