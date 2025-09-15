<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\PostActionInterface;
use App\StateMachine\Condition\ConditionInterface;
use App\StateMachine\State\StateInterface;

final class Transition implements TransitionInterface
{
    private ?ActionInterface $action = null;
    /**
     * @var PostActionInterface[]
     */
    private array $postActions = [];
    private bool $pauseAfterTransition = false;

    /**
     * @param ConditionInterface[] $conditions
     */
    public function __construct(
        private readonly StateInterface $fromState,
        private array $conditions = [],
        private ?StateInterface $toState = null,
    ) {
    }

    public function getFromState(): StateInterface
    {
        return $this->fromState;
    }

    public function getToState(): ?StateInterface
    {
        return $this->toState;
    }

    public function withAction(ActionInterface $action): TransitionInterface
    {
        if (null !== $this->action) {
            throw new TransitionActionAlreadyDefined();
        }
        $this->action = $action;

        return $this;
    }

    public function then(StateInterface $toState): TransitionInterface
    {
        $this->toState = $toState;

        return $this;
    }

    /**
     * @param PostActionInterface[] $postActions
     */
    public function withPostActions(array $postActions): TransitionInterface
    {
        $this->postActions = $postActions;

        return $this;
    }

    public function withPauseAfterTransition(): self
    {
        $this->pauseAfterTransition = true;

        return $this;
    }

    public function isPaused(): bool
    {
        return $this->pauseAfterTransition;
    }

    /**
     * @return PostActionInterface[]
     */
    public function getPostActions(): array
    {
        return $this->postActions;
    }

    public function getAction(): ?ActionInterface
    {
        return $this->action;
    }

    /**
     * @return ConditionInterface[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
