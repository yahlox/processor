<?php

declare(strict_types=1);

namespace Yahlox\Domain;

/**
 * Represents a directed edge that connects workflow nodes.
 *
 * Supports optional metadata for conditional routing and edge labeling.
 *
 * @package Yahlox
 */
final class Edge
{
    public function __construct(
        private readonly string $source,
        private readonly string $target,
        private readonly array $metadata = []
    ) {
    }

    /**
     * Get edge source node ID.
     *
     * @return string
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * Get edge target node ID.
     *
     * @return string
     */
    public function target(): string
    {
        return $this->target;
    }

    /**
     * Get edge metadata (conditions, labels, etc.).
     *
     * @return array|null
     */
    public function metadata(): ?array
    {
        return $this->metadata ?: null;
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
