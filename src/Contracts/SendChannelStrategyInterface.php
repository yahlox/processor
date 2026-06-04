<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;

interface SendChannelStrategyInterface
{
    public function send(array $payload, ExecutionContext $context, array $config = []): array;
}
