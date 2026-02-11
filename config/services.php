<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sopay' => [
        'endpoint' => env('SOPAY_ENDPOINT'),
        'app_id' => env('SOPAY_APP_ID'),
        'app_key' => env('SOPAY_APP_KEY'),
        'callback_url' => env('SOPAY_CALLBACK_URL'),
        'return_url' => env('SOPAY_RETURN_URL'),
    ],

    /*
    | Play API Admin 接口（后台调用 API 通知等）
    | 对应 API 路由前缀 x7k9m2p4，需 API Key 验证
    */
    'play_api_admin' => [
        'base_url' => env('PLAY_API_ADMIN_BASE_URL', ''),
        'api_key' => env('PLAY_API_ADMIN_API_KEY', ''),
    ],

];
