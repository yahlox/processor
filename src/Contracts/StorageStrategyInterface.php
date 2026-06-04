<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\ExecutionContext;

/**
 * Defines the contract for storage strategies supporting CRUD and read operations.
 *
 * @package Yahlox
 */
interface StorageStrategyInterface
{
/**
 * Create a new record in storage for the given model and payload.
 *
 * @param string $model Model class name or short alias.
 * @param array $payload Data payload for the record operation.
 * @param ExecutionContext $context Current workflow execution context.
 * @param array $metadata Optional metadata from the workflow node.
 * @return array
 */
    public function create(string $model, array $payload, ExecutionContext $context, array $metadata = []): array;

/**
 * Update an existing record in storage with the supplied payload.
 *
 * @param string $model Model class name or short alias.
 * @param string $recordId Identifier of the record to operate on.
 * @param array $payload Data payload for the record operation.
 * @param ExecutionContext $context Current workflow execution context.
 * @param array $metadata Optional metadata from the workflow node.
 * @return array
 */
    public function update(string $model, string $recordId, array $payload, ExecutionContext $context, array $metadata = []): array;

/**
 * Remove a record from storage by identifier.
 *
 * @param string $model Model class name or short alias.
 * @param string $recordId Identifier of the record to operate on.
 * @param ExecutionContext $context Current workflow execution context.
 * @param array $metadata Optional metadata from the workflow node.
 * @return array
 */
    public function delete(string $model, string $recordId, ExecutionContext $context, array $metadata = []): array;

/**
 * Retrieve a record or records from storage by ID or query metadata.
 *
 * @param string $model Model class name or short alias.
 * @param ?string $recordId Identifier of the record to operate on.
 * @param ExecutionContext $context Current workflow execution context.
 * @param array $metadata Optional metadata from the workflow node.
 * @return ?array
 */
    public function find(string $model, ?string $recordId, ExecutionContext $context, array $metadata = []): ?array;
}
