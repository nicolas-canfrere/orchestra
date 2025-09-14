<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextWriterInterface;
use Doctrine\DBAL\Connection;

final readonly class DBALProcessExecutionContextWriter implements ProcessExecutionContextWriterInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(ProcessExecutionContextInterface $processExecutionContext): void
    {
        $sql = <<<SQL
INSERT INTO public.process_execution_context
    (process_id, status, last_state_name, executed_transitions, failure)
VALUES
    (:process_id, :status, :last_state_name, :executed_transitions, :failure);
SQL;
        $this->connection->executeQuery(
            $sql,
            [
                'process_id' => $processExecutionContext->getProcessId(),
                'status' => $processExecutionContext->getStatus()->value,
                'last_state_name' => $processExecutionContext->getLastState()->getName(),
                'executed_transitions' => \json_encode($processExecutionContext->getExecutedTransitions()),
                'failure' => $processExecutionContext->getException()?->getTrace(),
            ]
        );
    }
}
