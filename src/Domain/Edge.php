<?php

declare(strict_types=1);

namespace Yahlox\Domain;

/**
 * Represents a directed edge that connects workflow nodes.
 *
 * @package Yahlox
 */
final class Edge
{
    public function __construct(
        private readonly string $source,
        private readonly string $target
    ) {
    }

/**
 * Source.
 * @return string
 */
    public function source(): string
    {
        return $this->source;
    }

/**
 * Target.
 * @return string
 */
    public function target(): string
    {
        return $this->target;
    }
}