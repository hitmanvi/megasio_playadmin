<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SiteConfigController extends Controller
{
    /**
     * 返回站点基础配置（如域名等）
     */
    public function index(): JsonResponse
    {
        $url = config('app.frontend_url');

        return $this->responseItem([
            'frontend_url' => $url ?? '',
            'customer_io_webhook_url' => config('services.customer_io.webhook_url'),
        ]);
    }
}
