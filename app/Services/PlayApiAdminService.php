<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 与 play_api Admin 接口交互的 Service
 * 用于后台在操作完成后通知 API（需 API Key 验证）
 */
class PlayApiAdminService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $prefix = 'x7k9m2p4';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.play_api_admin.base_url', ''), '/');
        $this->apiKey = config('services.play_api_admin.api_key', '');
    }

    /**
     * 请求是否可用（已配置 base_url 与 api_key）
     */
    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '';
    }

    /**
     * 通知 API：KYC 已完成（如后台审核通过后调用）
     *
     * @param  array{user_id: int, kyc_id?: int, status?: string}  $payload
     * @return array{success: bool, body?: array, status?: int, message?: string}
     */
    public function notifyKycCompleted(array $payload): array
    {
        if (!$this->isConfigured()) {
            Log::warning('PlayApiAdminService: play_api_admin not configured, skip notifyKycCompleted');

            return ['success' => false, 'message' => 'service not configured'];
        }

        $url = $this->baseUrl . '/' . $this->prefix . '/kyc/completed';

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $body = $response->json();
            $success = $response->successful();

            if (!$success) {
                Log::warning('PlayApiAdminService::notifyKycCompleted failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $body,
                    'payload' => $payload,
                ]);
            }

            return [
                'success' => $success,
                'status' => $response->status(),
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('PlayApiAdminService::notifyKycCompleted exception', [
                'url' => $url,
                'payload' => $payload,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
