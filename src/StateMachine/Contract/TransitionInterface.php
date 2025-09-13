<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

use App\StateMachine\Transition;

interface TransitionInterface
{
    public function getFromState(): StateInterface;

    public function getToState(): StateInterface;

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
}
