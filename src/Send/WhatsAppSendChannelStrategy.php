<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class WhatsAppSendChannelStrategy implements SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $to = $payload['to'] ?? null;
        $message = $payload['message'] ?? '';

        if (!$to) {
            return ['success' => false, 'error' => 'Missing "to" WhatsApp number'];
        }

        // In a real scenario, integrate with WhatsApp Business API
        $context->set('last_whatsapp_sent', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'channel' => 'whatsapp',
            'to' => $to,
            'message' => $message,
        ];
    }
}
