<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Workflow;

/**
 * Defines the contract for workflow parsers.
 *
 * @package Yahlox
 */
interface ParserInterface
{
    /**
     * Parse workflow JSON into a Yahlox workflow object.
     *
     * @param array $payload Data payload for the record operation.
     * @return Workflow
     */
    public function parse(array $payload): Workflow;
}
