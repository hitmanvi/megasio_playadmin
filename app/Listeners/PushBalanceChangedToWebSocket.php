<?php

namespace App\Listeners;

use App\Events\BalanceChanged;
use App\Models\User;
use App\Services\WebSocketService;

class PushBalanceChangedToWebSocket
{
    public function __construct(
        protected WebSocketService $webSocketService
    ) {}

    public function handle(BalanceChanged $event): void
    {
        $uid = User::query()->whereKey($event->userId)->value('uid');
        if ($uid === null || $uid === '') {
            return;
        }

        $data = [
            'currency' => $event->balance->currency,
            'available' => (string) $event->balance->available,
            'frozen' => (string) $event->balance->frozen,
            'amount' => (string) $event->amount,
            'operation' => $event->operation,
            'type' => $event->type,
            'updated_at' => $event->balance->updated_at->toIso8601String(),
        ];

        $this->webSocketService->sendToUser((string) $uid, 'balance.changed', $data);
    }
}
