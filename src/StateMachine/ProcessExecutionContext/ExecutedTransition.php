<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\Contract\TransitionInterface;

final class ExecutedTransition
{
    private function __construct(
        public readonly TransitionInterface $transition,
        public readonly \DateTimeImmutable $executedAt,
    ) {
    }

    public static function create(TransitionInterface $transition): self
    {
        return new self($transition, new \DateTimeImmutable());
    }
}
