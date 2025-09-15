<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

/**
 * @phpstan-type executedTransitionArray array{
 *     executedAt: \DateTimeImmutable,
 *     transition: array{
 *         fromState: string,
 *         toState: string,
 *     }
 * }
 */
final readonly class ProcessExecutionContextReadModel
{
    /**
     * @param executedTransitionArray[] $executedTransitions
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $processId,
        public ProcessExecutionContextStatusEnum $status,
        public string $lastStateName,
        public array $executedTransitions,
        public \DateTimeImmutable $createdAt,
        public array $parameters = [],
        public ?string $failure = null,
    ) {
    }
}
