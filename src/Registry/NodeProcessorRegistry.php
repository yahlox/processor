<?php

declare(strict_types=1);

namespace Yahlox\Registry;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Exceptions\NodeProcessorNotFoundException;

final class NodeProcessorRegistry
{
    private const PROCESSOR_NAMESPACE = 'Yahlox\\Processors\\';

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
            $this->autoRegisterProcessor($type);
        }

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
        return isset($this->processors[$type]) || $this->resolveProcessorClass($type) !== null;
    }

    private function autoRegisterProcessor(string $type): void
    {
        $processorClass = $this->resolveProcessorClass($type);

        if ($processorClass === null) {
            return;
        }

        $this->register($type, new $processorClass());
    }

    private function resolveProcessorClass(string $type): ?string
    {
        $processorName = $this->normalizeProcessorName($type);
        $class = self::PROCESSOR_NAMESPACE . $processorName;

        return class_exists($class) ? $class : null;
    }

    private function normalizeProcessorName(string $type): string
    {
        $normalized = str_replace(['-', '_'], ' ', $type);
        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $parts = array_map(static fn (string $part): string => ucfirst($part), $parts);

        return implode('', $parts) . 'NodeProcessor';
    }
}