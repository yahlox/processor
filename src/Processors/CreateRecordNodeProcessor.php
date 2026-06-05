<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Engine\ExpressionEvaluator;
use Yahlox\Engine\SagaCoordinator;
use Yahlox\Engine\TransactionManager;
use Yahlox\Storage\StorageHelpersTrait;
use Yahlox\Storage\StorageStrategyManager;
use Yahlox\Utils\InputSanitizer;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Creates a new record in storage with transaction and saga support.
 *
 * Features:
 * - Safe placeholder resolution
 * - Database transaction support
 * - Compensation (rollback) for saga pattern
 * - Input validation and sanitization
 * - Comprehensive logging
 *
 * @package Yahlox
 */
final class CreateRecordNodeProcessor implements NodeProcessorInterface
{
    use StorageHelpersTrait;

    private StorageStrategyManager $storageManager;
    private ExpressionEvaluator $expressionEvaluator;
    private ?SagaCoordinator $sagaCoordinator = null;
    private ?TransactionManager $transactionManager = null;
    private LoggerInterface $logger;

    /**
     * Construct CreateRecordNodeProcessor.
     *
     * @param ?StorageStrategyManager $storageManager
     * @param ?ExpressionEvaluator $expressionEvaluator
     * @param ?SagaCoordinator $sagaCoordinator
     * @param ?TransactionManager $transactionManager
     * @param ?LoggerInterface $logger
     */
    public function __construct(
        ?StorageStrategyManager $storageManager = null,
        ?ExpressionEvaluator $expressionEvaluator = null,
        ?SagaCoordinator $sagaCoordinator = null,
        ?TransactionManager $transactionManager = null,
        ?LoggerInterface $logger = null
    ) {
        $this->storageManager = $storageManager ?? StorageStrategyManager::createDefault();
        $this->expressionEvaluator = $expressionEvaluator ?? new ExpressionEvaluator();
        $this->sagaCoordinator = $sagaCoordinator;
        $this->transactionManager = $transactionManager;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Process create record node.
     *
     * @param Node $node
     * @param ExecutionContext $context
     * @return void
     * @throws RuntimeException
     */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $model = $data['model'] ?? 'GenericRecord';
        $fields = $data['fields'] ?? [];
        $useTransaction = $data['transaction'] ?? false;
        $connection = $data['connection'] ?? 'default';
        $compensationHandler = $data['compensationHandler'] ?? null;
        $storeAs = $data['storeAs'] ?? null;

        try {
            // Resolve and validate field values
            $resolved = [];
            foreach ($fields as $key => $value) {
                $resolvedValue = $this->expressionEvaluator->evaluate($value, $context);

                // Sanitize based on field type if specified
                if (isset($data['fieldTypes'][$key])) {
                    $fieldType = $data['fieldTypes'][$key];
                    $resolvedValue = InputSanitizer::sanitize($resolvedValue, $fieldType);
                }

                $resolved[$key] = $resolvedValue;
            }

            // Prepare config
            $config = $this->resolveStorageConfig($data, $context);

            // Execute with transaction if requested
            if ($useTransaction && $this->transactionManager) {
                $result = $this->executeWithTransaction(
                    $model,
                    $resolved,
                    $config,
                    $context,
                    $node->id()
                );
            } else {
                $result = $this->executeCreate($model, $resolved, $config, $context);
            }

            // Store result
            if ($result['success'] ?? false) {
                if ($storeAs) {
                    $context->set($storeAs, $result['data'] ?? $resolved);
                    $context->set("{$storeAs}_id", $result['id'] ?? uniqid('rec_', true));
                }

                $this->logger->info(
                    'Record created successfully',
                    ['model' => $model, 'id' => $result['id'] ?? null]
                );

                // Register compensation if saga coordinator provided
                if ($compensationHandler && $this->sagaCoordinator) {
                    $this->registerCompensation(
                        $node->id(),
                        $result['id'] ?? null,
                        $compensationHandler,
                        $context
                    );
                }
            } else {
                throw new RuntimeException(
                    "Failed to create record: " . ($result['error'] ?? 'Unknown error')
                );
            }

        } catch (RuntimeException $e) {
            $this->logger->error(
                'Failed to create record: ' . $e->getMessage(),
                ['model' => $model, 'node' => $node->id()]
            );
            throw $e;
        }
    }

    /**
     * Execute create within a transaction.
     *
     * @param string $model
     * @param array $fields
     * @param array $config
     * @param ExecutionContext $context
     * @param string $stepId
     * @return array
     */
    private function executeWithTransaction(
        string $model,
        array $fields,
        array $config,
        ExecutionContext $context,
        string $stepId
    ): array {
        $this->transactionManager->begin($config['connection'] ?? 'default');

        try {
            $result = $this->executeCreate($model, $fields, $config, $context);
            $this->transactionManager->commit($config['connection'] ?? 'default');
            return $result;
        } catch (RuntimeException $e) {
            $this->transactionManager->rollback($config['connection'] ?? 'default');
            throw $e;
        }
    }

    /**
     * Execute the actual record creation.
     *
     * @param string $model
     * @param array $fields
     * @param array $config
     * @param ExecutionContext $context
     * @return array Result with 'success', 'id', 'data', 'error'
     */
    private function executeCreate(
        string $model,
        array $fields,
        array $config,
        ExecutionContext $context
    ): array {
        $strategy = $this->storageManager->resolve($config + ['model' => $model], $context);
        return $strategy->create($model, $fields, $context, $config);
    }

    /**
     * Register compensation for saga pattern.
     *
     * @param string $stepId
     * @param ?string $recordId
     * @param string $compensationHandler
     * @param ExecutionContext $context
     * @return void
     */
    private function registerCompensation(
        string $stepId,
        ?string $recordId,
        string $compensationHandler,
        ExecutionContext $context
    ): void {
        if (!$this->sagaCoordinator) {
            return;
        }

        $compensation = function (ExecutionContext $ctx) use ($recordId, $compensationHandler) {
            // Call the compensation handler
            if (function_exists($compensationHandler)) {
                $compensationHandler($recordId, $ctx);
            }
        };

        $this->sagaCoordinator->registerCompensation($stepId, $compensation);
    }

    /**
     * Set saga coordinator.
     *
     * @param SagaCoordinator $coordinator
     * @return void
     */
    public function setSagaCoordinator(SagaCoordinator $coordinator): void
    {
        $this->sagaCoordinator = $coordinator;
    }

    /**
     * Set transaction manager.
     *
     * @param TransactionManager $manager
     * @return void
     */
    public function setTransactionManager(TransactionManager $manager): void
    {
        $this->transactionManager = $manager;
    }

    /**
     * Set logger.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
