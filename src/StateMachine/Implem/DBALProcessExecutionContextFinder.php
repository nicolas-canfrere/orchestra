<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFinderInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextReadModel;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use Doctrine\DBAL\Connection;

/**
 * @phpstan-type ProcessExecutionContextRow array{
 *     process_id: string,
 *     status: string,
 *     last_state_name: string,
 *     executed_transitions: string,
 *     created_at: string,
 *     failure: string|null,
 *     parameters: string,
 * }
 * @phpstan-type rawExecutedTransition array{
 *     executedAt: string,
 *     transition: string,
 * }
 *
 * @phpstan-import-type executedTransitionArray from ProcessExecutionContextReadModel
 */
final class DBALProcessExecutionContextFinder implements ProcessExecutionContextFinderInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findOneByProcessId(string $processId): ?ProcessExecutionContextReadModel
    {
        $sql = <<<'SQL'
SELECT * FROM public.process_execution_context WHERE process_id = :process_id;
SQL;
        /** @var ProcessExecutionContextRow|false $result */
        $result = $this->connection->fetchAssociative($sql, ['process_id' => $processId]);
        if (false === $result) {
            return null;
        }
        /** @var rawExecutedTransition[] $rawExecutedTransitions */
        $rawExecutedTransitions = \json_decode($result['executed_transitions'], true);
        /** @var executedTransitionArray[] $executedTransitions */
        $executedTransitions = [];
        foreach ($rawExecutedTransitions as $rawExecutedTransition) {
            $executedAt = \DateTimeImmutable::createFromFormat(
                \DateTimeInterface::ATOM,
                $rawExecutedTransition['executedAt']
            );
            if (false === $executedAt) {
                throw new \InvalidArgumentException('Date error');
            }
            /** @var string[] $transitionParts */
            $transitionParts = explode('::', $rawExecutedTransition['transition']);
            $executedTransitions[] = [
                'executedAt' => $executedAt,
                'transition' => [
                    'fromState' => $transitionParts[0],
                    'toState' => $transitionParts[1],
                ],
            ];
        }
        $createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $result['created_at']);
        if (false === $createdAt) {
            throw new \InvalidArgumentException('Date error');
        }
        /** @var array<string, mixed> $parameters */
        $parameters = \json_decode($result['parameters'], true, flags: JSON_THROW_ON_ERROR);

        return new ProcessExecutionContextReadModel(
            $result['process_id'],
            ProcessExecutionContextStatusEnum::from($result['status']),
            $result['last_state_name'],
            $executedTransitions,
            $createdAt,
            $parameters,
            $result['failure'] ?? null,
        );
    }
}
