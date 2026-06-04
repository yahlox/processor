<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class TelegramSendChannelStrategy implements SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $to = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';

        if (!$to) {
            return ['success' => false, 'error' => 'Missing "to" Telegram chat ID'];
        }

        // In a real scenario, integrate with Telegram Bot API
        $context->set('last_telegram_sent', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'channel' => 'telegram',
            'to' => $to,
            'message' => $message,
        ];
    }
}
