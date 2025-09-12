<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;

final readonly class Transition implements TransitionInterface
{
    public function __construct(
        private StateInterface $fromState,
        private StateInterface $toState,
    ) {
    }

    public function getFromState(): StateInterface
    {
        return $this->fromState;
    }

    public function getToState(): StateInterface
    {
        return $this->toState;
    }
}
