<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;

/**
 * Error node processor handles workflow errors and exceptions.
 * Allows workflow to define error handling paths and logging.
 *
 * @package Yahlox
 */
final class ErrorNodeProcessor implements NodeProcessorInterface
{
    /**
     * Process an error node.
     *
     * @param Node $node Error node to process
     * @param ExecutionContext $context Workflow execution context
     * @return void
     */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $errorMessage = $data['message'] ?? 'Workflow error encountered';
        $logError = $data['log'] ?? true;
        $stopExecution = $data['stopExecution'] ?? false;
        $storeAs = $data['storeAs'] ?? 'workflow_error';

        // Store error information
        $errorInfo = [
            'message' => $errorMessage,
            'timestamp' => now(),
            'context' => $context->all(),
        ];

        $context->set($storeAs, $errorInfo);

        // Log if enabled
        if ($logError) {
            $this->logError($errorMessage, $errorInfo);
        }

        // Mark error in context
        $context->set('__error_occurred', true);
        $context->set('__error_message', $errorMessage);

        // Stop execution if requested
        if ($stopExecution) {
            $context->set('__stop_execution', true);
        }
    }

    /**
     * Log error information.
     *
     * @param string $message Error message
     * @param array $info Error context
     * @return void
     */
    private function logError(string $message, array $info): void
    {
        $logMessage = "Workflow Error: {$message}\n";
        $logMessage .= "Timestamp: " . $info['timestamp'] . "\n";
        $logMessage .= "Context: " . json_encode($info['context'], JSON_PRETTY_PRINT);

        // Log to file or error log
        error_log($logMessage);
    }
}
