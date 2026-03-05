<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AdminAuthController extends Controller
{
    private const TWO_FACTOR_BIND_CACHE_PREFIX = 'admin_2fa_bind:';
    private const TWO_FACTOR_REBIND_CACHE_PREFIX = 'admin_2fa_rebind:';
    private const TWO_FACTOR_CACHE_TTL = 300; // 5 minutes

    /**
     * Admin login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
            'two_factor_code' => 'nullable|string|size:6',
        ]);

        $admin = Admin::where('name', $request->name)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        if ($admin->hasTwoFactorEnabled()) {
            if (!$request->filled('two_factor_code')) {
                return $this->error(Err::REQUIRES_TWO_FACTOR);
            }

            $google2fa = new Google2FA;
            if (!$google2fa->verifyKey($admin->two_factor_secret, $request->two_factor_code)) {
                return $this->error(Err::INVALID_PARAMS);
            }
        }

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
        $admin = $request->user();

        return $this->responseItem([
            'id' => $admin->id,
            'name' => $admin->name,
            'two_factor_enabled' => $admin->hasTwoFactorEnabled(),
        ]);
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($validated['current_password'], $admin->password)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $admin->update(['password' => $validated['password']]);

        return $this->responseItem(['updated' => true]);
    }

    /**
     * Bind two-factor authentication (step 1: get secret and QR URL)
     */
    public function bindTwoFactor(Request $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->hasTwoFactorEnabled()) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $provisioningUri = $google2fa->getQRCodeUrl(
            config('app.name', 'Admin'),
            $admin->name,
            $secret
        );

        Cache::put(self::TWO_FACTOR_BIND_CACHE_PREFIX . $admin->id, $secret, self::TWO_FACTOR_CACHE_TTL);

        return $this->responseItem([
            'secret' => $secret,
            'provisioning_uri' => $provisioningUri,
        ]);
    }

    /**
     * Confirm two-factor binding (step 2: verify code and save)
     */
    public function bindTwoFactorConfirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $admin = $request->user();
        $secret = Cache::pull(self::TWO_FACTOR_BIND_CACHE_PREFIX . $admin->id);

        if (!$secret) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $google2fa = new Google2FA;
        if (!$google2fa->verifyKey($secret, $request->code)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $admin->update(['two_factor_secret' => $secret]);

        return $this->responseItem(['bound' => true]);
    }

    /**
     * Rebind two-factor authentication (step 1: get new secret)
     */
    public function rebindTwoFactor(Request $request): JsonResponse
    {
        $admin = $request->user();

        if (!$admin->hasTwoFactorEnabled()) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $provisioningUri = $google2fa->getQRCodeUrl(
            config('app.name', 'Admin'),
            $admin->name,
            $secret
        );

        Cache::put(self::TWO_FACTOR_REBIND_CACHE_PREFIX . $admin->id, $secret, self::TWO_FACTOR_CACHE_TTL);

        return $this->responseItem([
            'secret' => $secret,
            'provisioning_uri' => $provisioningUri,
        ]);
    }

    /**
     * Confirm two-factor rebind (step 2: verify current + new code, save)
     */
    public function rebindTwoFactorConfirm(Request $request): JsonResponse
    {
        $request->validate([
            'current_code' => 'required|string|size:6',
            'new_code' => 'required|string|size:6',
        ]);

        $admin = $request->user();
        $newSecret = Cache::pull(self::TWO_FACTOR_REBIND_CACHE_PREFIX . $admin->id);

        if (!$newSecret) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $google2fa = new Google2FA;

        if (!$google2fa->verifyKey($admin->two_factor_secret, $request->current_code)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        if (!$google2fa->verifyKey($newSecret, $request->new_code)) {
            return $this->error(Err::INVALID_PARAMS);
        }

        $admin->update(['two_factor_secret' => $newSecret]);

        return $this->responseItem(['rebound' => true]);
    }
}
