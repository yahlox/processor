<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;

/**
 * Defines the contract for send channel strategies used by communication nodes.
 *
 * @package Yahlox
 */
interface SendChannelStrategyInterface
{
    /**
     * Send.
     * @param array $payload Data payload for the record operation.
     * @param ExecutionContext $context Current workflow execution context.
     * @param array $config Configuration block from the workflow node.
     * @return array
     */
    public function send(array $payload, ExecutionContext $context, array $config = []): array;
}
