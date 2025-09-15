<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

interface ProcessExecutionContextFinderInterface
{
    public function findOneByProcessId(string $processId): ?ProcessExecutionContextReadModel;
}
