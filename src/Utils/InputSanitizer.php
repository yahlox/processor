<?php

declare(strict_types=1);

namespace Yahlox\Utils;

use RuntimeException;

/**
 * Input sanitization and escaping utilities for SQL, HTML, URLs, etc.
 *
 * @package Yahlox
 */
final class InputSanitizer
{
    /**
     * Sanitize string input for SQL queries (if not using prepared statements).
     * WARNING: This is a fallback. Always prefer prepared statements!
     *
     * @param mixed $input Input to sanitize
     * @param string $type Type of context: 'string', 'number', 'boolean', 'json'
     * @return mixed Sanitized value
     */
    public static function sanitize(mixed $input, string $type = 'string'): mixed
    {
        return match ($type) {
            'string' => self::sanitizeString($input),
            'number' => self::sanitizeNumber($input),
            'boolean' => self::sanitizeBoolean($input),
            'json' => self::sanitizeJson($input),
            'email' => self::sanitizeEmail($input),
            'url' => self::sanitizeUrl($input),
            'html' => self::sanitizeHtml($input),
            default => $input,
        };
    }

    /**
     * Sanitize string input.
     *
     * @param mixed $input
     * @return string
     */
    private static function sanitizeString(mixed $input): string
    {
        if (!is_string($input)) {
            $input = (string)$input;
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Remove control characters
        $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input) ?? $input;

        return trim($input);
    }

    /**
     * Sanitize numeric input.
     *
     * @param mixed $input
     * @return int|float|null
     * @throws RuntimeException
     */
    private static function sanitizeNumber(mixed $input): int|float|null
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_numeric($input)) {
            return (float)$input;
        }

        throw new RuntimeException("Invalid number: {$input}");
    }

    /**
     * Sanitize boolean input.
     *
     * @param mixed $input
     * @return bool
     */
    private static function sanitizeBoolean(mixed $input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if (is_string($input)) {
            return in_array(strtolower($input), ['true', '1', 'yes', 'on'], true);
        }

        return (bool)$input;
    }

    /**
     * Sanitize JSON input.
     *
     * @param mixed $input
     * @return mixed Decoded JSON
     * @throws RuntimeException
     */
    private static function sanitizeJson(mixed $input): mixed
    {
        if (is_array($input) || is_object($input)) {
            return $input;
        }

        $input = (string)$input;
        $decoded = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Sanitize email address.
     *
     * @param mixed $input
     * @return string
     * @throws RuntimeException
     */
    private static function sanitizeEmail(mixed $input): string
    {
        $input = self::sanitizeString($input);

        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid email address: {$input}");
        }

        return $input;
    }

    /**
     * Sanitize URL.
     *
     * @param mixed $input
     * @return string
     * @throws RuntimeException
     */
    private static function sanitizeUrl(mixed $input): string
    {
        $input = self::sanitizeString($input);

        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid URL: {$input}");
        }

        return $input;
    }

    /**
     * Sanitize HTML content (escape for safe display).
     *
     * @param mixed $input
     * @return string
     */
    private static function sanitizeHtml(mixed $input): string
    {
        return htmlspecialchars((string)$input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape string for database queries (backup to prepared statements).
     *
     * @param mixed $input
     * @param \PDO|null $pdo PDO instance for context
     * @return string
     */
    public static function escapeSql(mixed $input, ?\PDO $pdo = null): string
    {
        if (is_numeric($input)) {
            return (string)$input;
        }

        $input = (string)$input;

        // Use PDO quote if available
        if ($pdo !== null) {
            return $pdo->quote($input);
        }

        // Fallback escaping
        return "'" . addslashes($input) . "'";
    }

    /**
     * Validate input against a pattern.
     *
     * @param mixed $input
     * @param string $pattern Regex pattern
     * @return bool
     */
    public static function validate(mixed $input, string $pattern): bool
    {
        return (bool)preg_match($pattern, (string)$input);
    }

    /**
     * Validate array keys are safe.
     *
     * @param array $input
     * @param string $pattern Regex pattern for keys
     * @return bool
     */
    public static function validateArrayKeys(array $input, string $pattern): bool
    {
        foreach (array_keys($input) as $key) {
            if (!preg_match($pattern, (string)$key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Remove dangerous characters for shell commands.
     *
     * @param mixed $input
     * @return string
     */
    public static function escapeShellArg(mixed $input): string
    {
        return escapeshellarg((string)$input);
    }
}
