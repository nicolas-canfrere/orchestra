<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\ActionInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;

final class Transition implements TransitionInterface
{
    private ?ActionInterface $action = null;

    public function __construct(
        private readonly StateInterface $fromState,
        private readonly StateInterface $toState,
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

    public function withAction(ActionInterface $action): TransitionInterface
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): ?ActionInterface
    {
        return $this->action;
    }
}
