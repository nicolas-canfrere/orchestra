<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;

final class State implements StateInterface
{
    private ?TransitionInterface $nextTransition = null;

    public function __construct(
        private readonly string $name,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('State name cannot be empty.');
        }
    }

    public function then(StateInterface $toState): TransitionInterface
    {
        $this->nextTransition = new Transition($this, $toState);

        return $this->nextTransition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNextTransition(): ?TransitionInterface
    {
        return $this->nextTransition;
    }
}
