<?php

declare(strict_types=1);

namespace Yahlox\Exceptions;

use RuntimeException;

/**
 * Thrown when a workflow is invalid or cannot be executed.
 *
 * @package Yahlox
 */
class InvalidWorkflowException extends RuntimeException
{
}