<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis pub/sub（与 WebSocket 网关约定一致）
    |--------------------------------------------------------------------------
    |
    | WebSocketService 通过 Redis::publish 将消息投递到这些 channel；具体由
    | 独立 WebSocket 服务订阅并推给客户端。
    |
    */

    'pubsub' => [
        'broadcast' => env('WEBSOCKET_PUBSUB_BROADCAST_CHANNEL', 'megasio:broadcast'),
        'private_prefix' => env('WEBSOCKET_PUBSUB_PRIVATE_PREFIX', 'megasio:private'),
    ],

];
