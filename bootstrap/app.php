<?php

use App\Enums\Err;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if (str_contains($e->getMessage(), 'database') && str_contains($e->getMessage(), 'not found')) {
                $resp = [
                    'code'   => Err::ACCOUNT_NOT_FOUND,
                    'errmsg' => 'Not Found',
                    'data'   => null,
                ];
        
                return response()->json($resp);
            }
        });
    })->create();
