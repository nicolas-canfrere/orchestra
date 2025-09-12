<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface StateInterface
{
    public function getName(): string;

    public function getNextTransition(): ?TransitionInterface;

    public function then(StateInterface $toState): TransitionInterface;
}
