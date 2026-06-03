<?php

declare(strict_types=1);

namespace Yahlox\Contracts;

use Yahlox\Domain\Workflow;

interface ParserInterface
{
    public function parse(array $payload): Workflow;
}