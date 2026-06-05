<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

/**
 * Logs send payloads for debugging or local execution.
 *
 * @package Yahlox
 */
final class LogSendChannelStrategy implements SendChannelStrategyInterface
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
        $context->set('last_send_log', $payload);

        return [
            'success' => true,
            'channel' => 'log',
            'payload' => $payload,
        ];
    }
}
