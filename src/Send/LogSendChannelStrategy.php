<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;

final class LogSendChannelStrategy implements SendChannelStrategyInterface
{
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
