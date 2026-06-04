<?php

declare(strict_types=1);

namespace Yahlox\Domain;

/**
 * Represents a workflow node including its type and configuration.
 *
 * @package Yahlox
 */
final class Node
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly array $data = []
    ) {
    }

/**
 * Id.
 * @return string
 */
    public function id(): string
    {
        return $this->id;
    }

/**
 * Type.
 * @return string
 */
    public function type(): string
    {
        return $this->type;
    }

/**
 * Data.
 * @return array
 */
    public function data(): array
    {
        return $this->data;
    }
}