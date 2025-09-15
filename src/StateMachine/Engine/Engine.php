<?php

declare(strict_types=1);

namespace App\StateMachine\Engine;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\PostActionsExecutorInterface;
use App\StateMachine\ProcessDefinition\ProcessDefinitionInterface;
use App\StateMachine\ProcessExecutionContext\ExecutedTransition;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextBuilder;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFinderInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextNotFoundException;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextWriterInterface;
use App\StateMachine\State\StateInterface;
use App\StateMachine\Transition\NextTransitionFinderInterface;

final readonly class Engine implements EngineInterface
{
    public function __construct(
        private ProcessExecutionContextFactory $contextFactory,
        private NextTransitionFinderInterface $nextTransitionFinder,
        private ProcessExecutionContextWriterInterface $processExecutionContextWriter,
        private ProcessExecutionContextFinderInterface $processExecutionContextFinder,
        private ProcessExecutionContextBuilder $processExecutionContextBuilder,
        private PostActionsExecutorInterface $postActionsExecutor,
    ) {
    }

    public function resume(ProcessDefinitionInterface $processDefinition, string $processId): void
    {
        $contextReadModel = $this->processExecutionContextFinder->findOneByProcessId($processId);
        if (null === $contextReadModel) {
            throw new ProcessExecutionContextNotFoundException(
                sprintf(
                    'Process "%s" does not found.',
                    $processId
                )
            );
        }
        if (ProcessExecutionContextStatusEnum::PAUSED !== $contextReadModel->status) {
            throw new ResumeForbiddenException(
                sprintf(
                    'Resume forbidden for process: %s as it is not paused',
                    $processId
                )
            );
        }
        $lastState = $processDefinition->stateByName($contextReadModel->lastStateName);
        if (null === $lastState) {
            return;
        }
        $processExecutionContext = $this->processExecutionContextBuilder->create(
            $contextReadModel->processId,
            $contextReadModel->status,
            $contextReadModel->createdAt,
            $contextReadModel->parameters,
        )
            ->withLastState($lastState)
            ->build();
        $processExecutionContext->setStatus(ProcessExecutionContextStatusEnum::RUNNING);

        $this->executeTransition($lastState, $processExecutionContext);
        $this->finishAndSaveContext($processExecutionContext);
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
                $this->executeAction($nextTransition->getAction(), $context->getParameters());
                $this->postActionsExecutor->executePostActions($nextTransition->getPostActions(), $context->getParameters());
                $context->setLastState($toState);
                $context->addExecutedTransition(ExecutedTransition::create($nextTransition));
                if ($nextTransition->isPaused()) {
                    $context->setStatus(ProcessExecutionContextStatusEnum::PAUSED);
                }
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

    private function finishAndSaveContext(ProcessExecutionContextInterface $context): void
    {
        if (ProcessExecutionContextStatusEnum::RUNNING === $context->getStatus()) {
            $context->setStatus(ProcessExecutionContextStatusEnum::FINISHED);
        }
        $this->processExecutionContextWriter->save($context);
    }
}
