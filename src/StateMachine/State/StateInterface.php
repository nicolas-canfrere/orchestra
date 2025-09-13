<?php

declare(strict_types=1);

namespace App\StateMachine\State;

use App\StateMachine\Transition\TransitionInterface;

interface StateInterface
{
    public function getName(): string;

    public function then(StateInterface $toState): TransitionInterface;

    /**
     * @return TransitionInterface[]
     */
    public function getNextTransitions(): array;
}
