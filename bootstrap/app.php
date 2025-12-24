<?php

use App\Enums\Err;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Exception $e) {
            Log::error($e->getMessage());
            $resp = [
                'code'   => 500,
                'errmsg' => $e->getMessage(),
                'data'   => null,
            ];
            return response()->json($resp);
        });
    })->create();
