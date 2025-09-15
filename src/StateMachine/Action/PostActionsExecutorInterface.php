<?php

declare(strict_types=1);

namespace App\StateMachine\Action;

interface PostActionsExecutorInterface
{
    /**
     * @param PostActionInterface[] $postActions
     * @param array<string, mixed> $parameters
     */
    public function executePostActions(array $postActions, array $parameters): void;
}
