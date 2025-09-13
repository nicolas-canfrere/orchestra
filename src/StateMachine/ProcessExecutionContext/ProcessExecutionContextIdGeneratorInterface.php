<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

interface ProcessExecutionContextIdGeneratorInterface
{
    public function generate(): string;
}
