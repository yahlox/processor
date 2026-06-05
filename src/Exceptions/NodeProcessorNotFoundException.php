<?php

declare(strict_types=1);

namespace Yahlox\Exceptions;

use RuntimeException;

/**
 * Thrown when a node processor cannot be resolved for a workflow node type.
 *
 * @package Yahlox
 */
class NodeProcessorNotFoundException extends RuntimeException
{
}
