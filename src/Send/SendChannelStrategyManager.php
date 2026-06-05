<?php

declare(strict_types=1);

namespace Yahlox\Send;

use Yahlox\Contracts\SendChannelStrategyInterface;
use Yahlox\Domain\ExecutionContext;
use RuntimeException;

/**
 * Resolves send channel strategies based on node configuration.
 *
 * @package Yahlox
 */
final class SendChannelStrategyManager
{
    private array $channels = [];
    private string $defaultChannel;

    /**
     * Construct a new SendChannelStrategyManager.
     * @param array $channels
     * @param string $defaultChannel
     * @return void
     */
    public function __construct(array $channels = [], string $defaultChannel = 'log')
    {
        foreach ($channels as $name => $strategy) {
            $this->register($name, $strategy);
        }

        $this->defaultChannel = $defaultChannel;
    }

    /**
     * Create the default manager with built-in strategies.
     *
     * @return self
     */
    public static function createDefault(): self
    {
        return new self([
            'log' => new LogSendChannelStrategy(),
            'email' => new EmailSendChannelStrategy(),
            'sms' => new SmsSendChannelStrategy(),
            'viber' => new ViberSendChannelStrategy(),
            'whatsapp' => new WhatsAppSendChannelStrategy(),
            'messenger' => new MessengerSendChannelStrategy(),
            'telegram' => new TelegramSendChannelStrategy(),
        ], 'log');
    }

    /**
     * Register a strategy by alias.
     *
     * @param string $name Registry or strategy name.
     * @param SendChannelStrategyInterface $strategy Resolved strategy instance.
     * @return void
     */
    public function register(string $name, SendChannelStrategyInterface $strategy): void
    {
        $this->channels[$name] = $strategy;
    }

    /**
     * Return the named registered strategy.
     *
     * @param string $name Registry or strategy name.
     * @return SendChannelStrategyInterface
     */
    public function get(string $name): SendChannelStrategyInterface
    {
        if (!isset($this->channels[$name])) {
            throw new RuntimeException(sprintf('Send channel strategy [%s] not found.', $name));
        }

        return $this->channels[$name];
    }

    /**
     * Check whether a named instance is registered.
     *
     * @param string $name Registry or strategy name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->channels[$name]);
    }

    /**
     * Resolve the correct strategy for the given workflow node data and context.
     *
     * @param array $data Workflow node data used for resolution.
     * @return SendChannelStrategyInterface
     */
    public function resolve(array $data): SendChannelStrategyInterface
    {
        $channel = $data['channel'] ?? $this->defaultChannel;

        if ($this->has($channel)) {
            return $this->get($channel);
        }

        return $this->get($this->defaultChannel);
    }
}
