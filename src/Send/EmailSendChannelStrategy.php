<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

/**
 * Sends email payloads using email channel credentials.
 *
 * @package Yahlox
 */
final class EmailSendChannelStrategy implements SendChannelStrategyInterface
{
    /**
     * Send.
     * @param array $payload Data payload for the record operation.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $config Configuration block from the workflow node.
     * @return array
     */
    public function send(array $payload, ExecutionContext $context, array $config = []): array
    {
        $to = $payload['to'] ?? null;
        $subject = $payload['subject'] ?? '';
        $body = $payload['body'] ?? '';

        if (!$to) {
            return ['success' => false, 'error' => 'Missing "to" address'];
        }

        // In a real scenario, integrate with mail service (e.g., Mailer, Mail::send)
        $context->set('last_email_sent', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);

        return [
            'success' => true,
            'channel' => 'email',
            'to' => $to,
            'subject' => $subject,
        ];
    }
}
