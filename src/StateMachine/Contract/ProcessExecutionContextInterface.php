<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

use App\StateMachine\ProcessExecutionContext\ExecutedTransition;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContext;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use App\StateMachine\Transition\TransitionInterface;

interface ProcessExecutionContextInterface
{
    public function getStatus(): ProcessExecutionContextStatusEnum;

    public function setLastState(?StateInterface $lastState): self;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getLastState(): ?StateInterface;

    public function setStatus(ProcessExecutionContextStatusEnum $status): self;

    public function getProcessId(): string;

    public function addExecutedTransition(ExecutedTransition $executedTransition): void;

    /**
     * @return ExecutedTransition[]
     */
    public function getExecutedTransitions(): array;

    public function getCurrentTransition(): ?TransitionInterface;

    public function setCurrentTransition(TransitionInterface $currentTransition): self;

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    public function getException(): ?\Throwable;

    public function setException(?\Throwable $exception): ProcessExecutionContext;
}
