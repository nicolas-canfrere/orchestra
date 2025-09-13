<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextWriterInterface;
use Psr\Log\LoggerInterface;

final class LogProcessExecutionContextWriter implements ProcessExecutionContextWriterInterface
{
    public function __construct(
        private readonly LoggerInterface $orchestraLogger,
    ) {
    }

    public function save(ProcessExecutionContextInterface $processExecutionContext): void
    {
        $this->orchestraLogger->info(
            sprintf(
                '[%s] %s',
                $processExecutionContext->getStatus()->value,
                $processExecutionContext->getProcessId()
            ),
            [
                'lastState' => $processExecutionContext->getLastState()?->getName(),
                'executedTransitions' => $processExecutionContext->getExecutedTransitions(),
                'failure' => $processExecutionContext->getException()?->getTrace(),
            ]
        );
    }
}
