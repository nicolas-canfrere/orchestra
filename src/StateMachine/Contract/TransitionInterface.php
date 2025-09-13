<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\PostActionInterface;

interface TransitionInterface
{
    public function getFromState(): StateInterface;

    public function getToState(): ?StateInterface;

    public function withAction(ActionInterface $action): TransitionInterface;

    public function getAction(): ?ActionInterface;

    /**
     * @return PostActionInterface[]
     */
    public function getPostActions(): array;

    /**
     * @param PostActionInterface[] $postActions
     */
    public function withPostActions(array $postActions): TransitionInterface;

    /**
     * @return ConditionInterface[]
     */
    public function getConditions(): array;

    public function then(StateInterface $toState): TransitionInterface;
}
