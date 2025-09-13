<?php

declare(strict_types=1);

namespace App\StateMachine\Engine;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\PostActionInterface;
use App\StateMachine\ProcessExecutionContext\ExecutedTransition;
use App\StateMachine\ProcessExecutionContext\ProcessDefinitionInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use App\StateMachine\State\StateInterface;
use App\StateMachine\Transition\NextTransitionFinderInterface;

final class Engine implements EngineInterface
{
    public function __construct(
        private readonly ProcessExecutionContextFactory $contextFactory,
        private readonly NextTransitionFinderInterface $nextTransitionFinder,
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
        $visitedStates = new \WeakMap();
        $nextTransition = $this->nextTransitionFinder->findStateNextTransition($context, $currentState);

        while (
            ProcessExecutionContextStatusEnum::RUNNING === $context->getStatus()
            && null !== $nextTransition
        ) {
            $toState = $nextTransition->getToState();
            // Detect circular transition
            if (isset($visitedStates[$toState])) {
                throw new CircularTransitionException('Circular transition detected');
            }
            $visitedStates[$toState] = true;
            try {
                $context->setCurrentTransition($nextTransition);
                $this->executeAction($nextTransition->getAction(), $context->getParameters());
                $this->executePostActions($nextTransition->getPostActions(), $context->getParameters());
                $context->setLastState($toState);
                $context->addExecutedTransition(ExecutedTransition::create($nextTransition));
                $nextTransition = $this->nextTransitionFinder->findStateNextTransition(
                    $context,
                    $toState,
                );
            } catch (\Throwable $exception) {
                $context->setStatus(ProcessExecutionContextStatusEnum::FAILED)
                    ->setException($exception);
            }
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function executeAction(?ActionInterface $action, array $parameters): void
    {
        $action?->run($parameters);
    }

    /**
     * @param PostActionInterface[] $postActions
     * @param array<string, mixed> $parameters
     */
    public function executePostActions(array $postActions, array $parameters): void
    {
        foreach ($postActions as $postAction) {
            try {
                $postAction->run($parameters);
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function finishAndSaveContext(ProcessExecutionContextInterface $context): void
    {
        if (ProcessExecutionContextStatusEnum::RUNNING === $context->getStatus()) {
            $context->setStatus(ProcessExecutionContextStatusEnum::FINISHED);
        }
    }
}
