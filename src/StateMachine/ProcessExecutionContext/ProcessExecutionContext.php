<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Transition\TransitionInterface;

final class ProcessExecutionContext implements ProcessExecutionContextInterface
{
    private ?StateInterface $lastState = null;
    /** @var ExecutedTransition[] */
    private array $executedTransitions = [];
    private ?TransitionInterface $currentTransition = null;
    private ?\Throwable $exception = null;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly string $processId,
        private ProcessExecutionContextStatusEnum $status,
        private readonly \DateTimeImmutable $createdAt,
        private readonly array $parameters,
    ) {
    }

    public function addExecutedTransition(ExecutedTransition $executedTransition): void
    {
        $this->executedTransitions[] = $executedTransition;
    }

    public function getExecutedTransitions(): array
    {
        return $this->executedTransitions;
    }

    public function getCurrentTransition(): ?TransitionInterface
    {
        return $this->currentTransition;
    }

    public function setCurrentTransition(TransitionInterface $currentTransition): self
    {
        $this->currentTransition = $currentTransition;

        return $this;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    public function getStatus(): ProcessExecutionContextStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ProcessExecutionContextStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastState(): ?StateInterface
    {
        return $this->lastState;
    }

    public function setLastState(?StateInterface $lastState): self
    {
        $this->lastState = $lastState;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function setException(?\Throwable $exception): ProcessExecutionContext
    {
        $this->exception = $exception;

        return $this;
    }
}
