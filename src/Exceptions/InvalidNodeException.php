<?php

declare(strict_types=1);

namespace Yahlox\Exceptions;

use RuntimeException;

/**
 * Thrown when a workflow node is invalid or missing required data.
 *
 * @package Yahlox
 */
class InvalidNodeException extends RuntimeException
{
}
