<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

/**
 * Sanitizes and validates user input across multiple types.
 *
 * @package Yahlox
 */
interface InputSanitizerInterface
{
    /**
     * Sanitize a string value.
     *
     * @param string $input Raw input
     * @return string Sanitized output
     */
    public function sanitizeString(string $input): string;

    /**
     * Sanitize and validate an email address.
     *
     * @param string $input Email address
     * @return string Sanitized email
     */
    public function sanitizeEmail(string $input): string;

    /**
     * Sanitize and validate a URL.
     *
     * @param string $input URL
     * @return string Sanitized URL
     */
    public function sanitizeUrl(string $input): string;

    /**
     * Sanitize HTML content.
     *
     * @param string $html HTML input
     * @return string Sanitized HTML
     */
    public function sanitizeHtml(string $html): string;

    /**
     * Sanitize and validate a number.
     *
     * @param string|int|float $input Number input
     * @return int|float Sanitized number
     */
    public function sanitizeNumber(string|int|float $input): int|float;
}
