<?php

declare(strict_types=1);

namespace Yahlox\Processors;

use Yahlox\Contracts\NodeProcessorInterface;
use Yahlox\Domain\ExecutionContext;
use Yahlox\Domain\Node;
use Yahlox\Send\SendChannelStrategyManager;
use RuntimeException;

/**
 * Sends notifications through configured send channel strategies.
 *
 * @package Yahlox
 */
final class SendNotificationNodeProcessor implements NodeProcessorInterface
{
    private SendChannelStrategyManager $channelManager;

/**
 * Construct a new SendNotificationNodeProcessor.
 * @param ?SendChannelStrategyManager $channelManager
 * @return void
 */
    public function __construct(?SendChannelStrategyManager $channelManager = null)
    {
        $this->channelManager = $channelManager ?? SendChannelStrategyManager::createDefault();
    }

/**
 * Execute processor logic for the workflow node and update the execution context.
 *
 * @param Node $node Workflow node to process.
 * @param ExecutionContext $context Current workflow execution context.
 * @return void
 */
    public function process(Node $node, ExecutionContext $context): void
    {
        $data = $node->data();
        $userId = $data['user_id'] ?? null;
        $title = $data['title'] ?? '';
        $body = $data['body'] ?? '';
        $channel = $data['channel'] ?? 'log';

        if (!$userId) {
            throw new RuntimeException('SendNotification node missing user_id');
        }

        $payload = [
            'to' => $this->resolvePlaceholders($userId, $context),
            'title' => $this->resolvePlaceholders($title, $context),
            'body' => $this->resolvePlaceholders($body, $context),
        ];

        $strategy = $this->channelManager->resolve(['channel' => $channel]);
        $result = $strategy->send($payload, $context, $data['config'] ?? []);

        $context->set('last_notification_sent', $payload);
        $context->set('last_send_result', $result);
    }

/**
 * Resolve placeholder tokens using values from the execution context.
 *
 * @param string $value Value to store or evaluate.
 * @param ExecutionContext $context Current workflow execution context.
 * @return string
 */
    private function resolvePlaceholders(string $value, ExecutionContext $context): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($context) {
            return $context->get($matches[1]) ?? '';
        }, $value);
    }
}
