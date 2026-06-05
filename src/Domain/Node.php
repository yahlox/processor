<?php

declare(strict_types=1);

namespace Yahlox\Domain;

/**
 * Represents a workflow node including its type, configuration, and metadata.
 *
 * @package Yahlox
 */
final class Node
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly array $data = [],
        private readonly ?array $position = null,
        private readonly array $metadata = []
    ) {
    }

    /**
     * Get node ID.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get node type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get node configuration data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Get node position (x, y coordinates).
     *
     * @return array|null Array with 'x' and 'y' keys, or null
     */
    public function position(): ?array
    {
        return $this->position;
    }

    /**
     * Get node metadata.
     *
     * @return array
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a metadata value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}