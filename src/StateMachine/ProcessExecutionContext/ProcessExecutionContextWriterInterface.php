<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

interface ProcessExecutionContextWriterInterface
{
    public function save(ProcessExecutionContextInterface $processExecutionContext): void;
}
