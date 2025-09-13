<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

use App\StateMachine\ProcessExecutionContext\ExecutedTransition;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContext;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;

interface ProcessExecutionContextInterface
{
    public function getStatus(): ProcessExecutionContextStatusEnum;

    public function setLastState(StateInterface $lastState): ProcessExecutionContext;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getLastState(): StateInterface;

    public function setStatus(ProcessExecutionContextStatusEnum $status): ProcessExecutionContext;

    public function getProcessId(): string;

    public function addExecutedTransition(ExecutedTransition $executedTransition): void;

    /**
     * @return ExecutedTransition[]
     */
    public function getExecutedTransitions(): array;

    public function getCurrentTransition(): TransitionInterface;

    public function setCurrentTransition(TransitionInterface $currentTransition): ProcessExecutionContext;

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array;
}
