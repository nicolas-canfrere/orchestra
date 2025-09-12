<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\ActionInterface;
use App\StateMachine\Contract\EngineInterface;
use App\StateMachine\Contract\ProcessDefinitionInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Exception\CircularTransitionException;

final class Engine implements EngineInterface
{
    public function launch(ProcessDefinitionInterface $processDefinition): void
    {
        $this->executeTransition($processDefinition->getStartState());
    }

    public function executeTransition(StateInterface $currentState): void
    {
        $visitedStates = new \SplObjectStorage();
        $nextTransition = $currentState->getNextTransition();

        while (null !== $nextTransition) {
            $toState = $nextTransition->getToState();

            // Detect circular transition
            if ($visitedStates->contains($toState)) {
                throw new CircularTransitionException('Circular transition detected');
            }

            $visitedStates->attach($toState);
            $this->executeAction($nextTransition->getAction());
            $nextTransition = $toState->getNextTransition();
        }
    }

    public function executeAction(?ActionInterface $action): void
    {
        $action?->run();
    }
}
