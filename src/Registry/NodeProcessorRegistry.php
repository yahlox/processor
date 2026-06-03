<?php

declare(strict_types=1);

namespace Yahlox\Registry;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Exceptions\NodeProcessorNotFoundException;

final class NodeProcessorRegistry
{
    private array $processors = [];

    public function register(
        string $type,
        NodeProcessorInterface $processor
    ): void {

        $this->processors[$type] = $processor;
    }

    public function get(
        string $type
    ): NodeProcessorInterface {

        if (!isset($this->processors[$type])) {

            throw new NodeProcessorNotFoundException(
                sprintf(
                    'Processor [%s] not found.',
                    $type
                )
            );
        }

        return $this->processors[$type];
    }

    public function has(string $type): bool
    {
        return isset($this->processors[$type]);
    }
}