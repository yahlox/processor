<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class ViberSendChannelStrategy implements SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $to = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';

        if (!$to) {
            return ['success' => false, 'error' => 'Missing "to" Viber ID'];
        }

        // In a real scenario, integrate with Viber Bot API
        $context->set('last_viber_sent', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'channel' => 'viber',
            'to' => $to,
            'message' => $message,
        ];
    }
}
