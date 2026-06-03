<?php

declare(strict_types=1);

namespace Yahlox\Domain;

final class Edge
{
    public function __construct(
        private readonly string $source,
        private readonly string $target
    ) {
    }

    public function source(): string
    {
        return $this->source;
    }

    public function target(): string
    {
        return $this->target;
    }
}