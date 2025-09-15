<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\State\StateInterface;

final class ProcessExecutionContextBuilder
{
    private ProcessExecutionContextInterface $processExecutionContext;

    /**
     * @param array<string, mixed> $parameters
     */
    public function create(
        string $processId,
        ProcessExecutionContextStatusEnum $status,
        \DateTimeImmutable $createdAt,
        array $parameters,
    ): self {
        $this->processExecutionContext = new ProcessExecutionContext(
            $processId,
            $status,
            $createdAt,
            $parameters,
        );

        return $this;
    }

    public function withLastState(?StateInterface $state): self
    {
        $this->processExecutionContext->setLastState($state);

        return $this;
    }

    public function build(): ProcessExecutionContextInterface
    {
        return $this->processExecutionContext;
    }
}
