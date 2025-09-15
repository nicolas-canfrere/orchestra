<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextWriterInterface;
use Doctrine\DBAL\Connection;

/**
 * @phpstan-import-type ProcessExecutionContextRow from DBALProcessExecutionContextFinder
 */
final readonly class DBALProcessExecutionContextWriter implements ProcessExecutionContextWriterInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(ProcessExecutionContextInterface $processExecutionContext): void
    {
        if ($this->exists($processExecutionContext->getProcessId())) {
            $this->update($processExecutionContext);

            return;
        }
        $this->insert($processExecutionContext);
    }

    public function update(ProcessExecutionContextInterface $processExecutionContext): void
    {
        $sql = <<<SQL
SELECT * FROM public.process_execution_context
WHERE process_id = :process_id;
SQL;
        /** @var ProcessExecutionContextRow|false $processExecutionContextFromDB */
        $processExecutionContextFromDB = $this->connection->fetchAssociative(
            $sql,
            ['process_id' => $processExecutionContext->getProcessId()]
        );
        if (false === $processExecutionContextFromDB) {
            throw new \RuntimeException();
        }
        /** @var array<int, array{executedAt: string, transition: string}> $executedTransitions */
        $executedTransitions = json_decode($processExecutionContextFromDB['executed_transitions'], true);
        $executedTransitions = array_merge($executedTransitions, $processExecutionContext->getExecutedTransitions());
        $sql = <<<SQL
UPDATE public.process_execution_context
SET status = :status,
    last_state_name = :last_state_name,
    executed_transitions = :executed_transitions,
    failure = :failure,
    parameters = :parameters
WHERE process_id = :process_id;
SQL;
        $this->connection->executeQuery(
            $sql,
            [
                'process_id' => $processExecutionContext->getProcessId(),
                'status' => $processExecutionContext->getStatus()->value,
                'last_state_name' => $processExecutionContext->getLastState()?->getName(),
                'executed_transitions' => \json_encode($executedTransitions),
                'failure' => $processExecutionContext->getException()?->getTrace(),
                'parameters' => \json_encode($processExecutionContext->getParameters()),
            ]
        );
    }

    private function exists(string $processId): bool
    {
        $sql = <<<SQL
SELECT EXISTS (
    SELECT 1
    FROM public.process_execution_context
    WHERE process_id = :process_id
);
SQL;
        /** @var bool $result */
        $result = $this->connection->fetchOne($sql, ['process_id' => $processId]);

        return $result;
    }

    private function insert(ProcessExecutionContextInterface $processExecutionContext): void
    {
        $sql = <<<SQL
INSERT INTO public.process_execution_context
    (process_id, status, last_state_name, executed_transitions, failure, parameters, created_at)
VALUES
    (:process_id, :status, :last_state_name, :executed_transitions, :failure, :parameters, :created_at);
SQL;
        $this->connection->executeQuery(
            $sql,
            [
                'process_id' => $processExecutionContext->getProcessId(),
                'status' => $processExecutionContext->getStatus()->value,
                'last_state_name' => $processExecutionContext->getLastState()?->getName(),
                'executed_transitions' => \json_encode($processExecutionContext->getExecutedTransitions()),
                'failure' => $processExecutionContext->getException()?->getTrace(),
                'parameters' => \json_encode($processExecutionContext->getParameters()),
                'created_at' => $processExecutionContext->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }
}
