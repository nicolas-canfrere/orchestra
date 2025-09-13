<?php

declare(strict_types=1);

namespace App\StateMachine;

use App\StateMachine\Contract\ActionInterface;
use App\StateMachine\Contract\EngineInterface;
use App\StateMachine\Contract\ProcessDefinitionInterface;
use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Exception\CircularTransitionException;
use App\StateMachine\ProcessExecutionContext\ExecutedTransition;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;

final class Engine implements EngineInterface
{
    public function __construct(
        private readonly ProcessExecutionContextFactory $contextFactory,
    ) {
    }

    public function launch(ProcessDefinitionInterface $processDefinition, array $parameters = []): void
    {
        $lastState = $processDefinition->getStartState();
        $processExecutionContext = $this->contextFactory->create($lastState, $parameters);
        $this->executeTransition($lastState, $processExecutionContext);
        $this->finishAndSaveContext($processExecutionContext);
    }

    public function executeTransition(
        StateInterface $currentState,
        ProcessExecutionContextInterface $context,
    ): ProcessExecutionContextInterface {
        $visitedStates = new \SplObjectStorage();
        $nextTransition = $currentState->getNextTransition();

        while (null !== $nextTransition) {
            $toState = $nextTransition->getToState();

            // Detect circular transition
            if ($visitedStates->contains($toState)) {
                throw new CircularTransitionException('Circular transition detected');
            }
            $visitedStates->attach($toState);

            $context->setCurrentTransition($nextTransition);
            $this->executeAction($nextTransition->getAction(), $context->getParameters());
            $context->setLastState($toState);
            $context->addExecutedTransition(ExecutedTransition::create($nextTransition));
            $nextTransition = $toState->getNextTransition();
        }

        $context->setStatus(ProcessExecutionContextStatusEnum::FINISHED);

        return $context;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function executeAction(?ActionInterface $action, array $parameters): void
    {
        $action?->run($parameters);
    }

    private function finishAndSaveContext(ProcessExecutionContextInterface $context): void
    {
        if (ProcessExecutionContextStatusEnum::RUNNING === $context->getStatus()) {
            $context->setStatus(ProcessExecutionContextStatusEnum::FINISHED);
        }
    }
}
