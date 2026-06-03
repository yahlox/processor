<?php

declare(strict_types=1);

namespace Yahlox\Domain;

final class Node
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly array $data = []
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function data(): array
    {
        return $this->data;
    }
}