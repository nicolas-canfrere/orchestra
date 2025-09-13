<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Condition\AlwaysValidCondition;
use App\StateMachine\Contract\ConditionInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;

final class State implements StateInterface
{
    /**
     * @var TransitionInterface[]
     */
    private array $nextTransitions = [];

    public function __construct(
        private readonly string $name,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('State name cannot be empty.');
        }
    }

    /**
     * @param ConditionInterface[] $conditions
     */
    public function when(array $conditions): TransitionInterface
    {
        $nextTransition = new Transition($this, conditions: $conditions);
        $this->nextTransitions[] = $nextTransition;

        return $nextTransition;
    }

    public function then(StateInterface $toState): TransitionInterface
    {
        $nextTransition = $this->when([new AlwaysValidCondition()]);
        $nextTransition->then($toState);

        return $nextTransition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getNextTransitions(): array
    {
        return $this->nextTransitions;
    }
}
