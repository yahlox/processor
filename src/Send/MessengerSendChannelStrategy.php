<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class MessengerSendChannelStrategy implements SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $to = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';

        if (!$to) {
            return ['success' => false, 'error' => 'Missing "to" Messenger recipient'];
        }

        // In a real scenario, integrate with Facebook Messenger API
        $context->set('last_messenger_sent', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'channel' => 'messenger',
            'to' => $to,
            'message' => $message,
        ];
    }
}
