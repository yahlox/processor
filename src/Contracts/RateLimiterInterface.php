<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

/**
 * Rate limiting interface for controlling operation frequency.
 *
 * @package Yahlox
 */
interface RateLimiterInterface
{
    /**
     * Check if operation is allowed under rate limit.
     *
     * @param string $key Unique identifier for the rate limit bucket
     * @return bool True if operation is allowed
     */
    public function allow(string $key): bool;

    /**
     * Get number of remaining operations before hitting the limit.
     *
     * @param string $key Unique identifier for the rate limit bucket
     * @return int Remaining operations
     */
    public function getRemaining(string $key): int;

    /**
     * Reset rate limit for a key.
     *
     * @param string $key Unique identifier for the rate limit bucket
     * @return void
     */
    public function reset(string $key): void;
}
