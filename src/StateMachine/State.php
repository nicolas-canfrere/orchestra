<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;

final readonly class State implements StateInterface
{
    public function __construct(
        private string $name,
    ) {
    }

    public function then(StateInterface $state): TransitionInterface
    {
        return new Transition($this, $state);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
