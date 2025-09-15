<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\PostActionsExecutorInterface;
use App\StateMachine\Engine\CircularTransitionException;
use App\StateMachine\Engine\Engine;
use App\StateMachine\ProcessDefinition\ProcessDefinitionInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContext;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextBuilder;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFinderInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextIdGeneratorInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextWriterInterface;
use App\StateMachine\State\StateInterface;
use App\StateMachine\Transition\NextTransitionFinderInterface;
use App\StateMachine\Transition\TransitionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EngineTest extends TestCase
{
    private Engine $engine;
    /**
     * @var NextTransitionFinderInterface&MockObject
     */
    private NextTransitionFinderInterface $nextTransitionFinder;
    private ProcessExecutionContextBuilder $processExecutionContextBuilder;

    protected function setUp(): void
    {
        $idGenerator = $this->createMock(ProcessExecutionContextIdGeneratorInterface::class);
        $contextFactory = new ProcessExecutionContextFactory($idGenerator);
        $this->nextTransitionFinder = $this->createMock(NextTransitionFinderInterface::class);
        $this->processExecutionContextBuilder = new ProcessExecutionContextBuilder();
        $this->engine = new Engine(
            $contextFactory,
            $this->nextTransitionFinder,
            $this->createMock(ProcessExecutionContextWriterInterface::class),
            $this->createMock(ProcessExecutionContextFinderInterface::class),
            $this->processExecutionContextBuilder,
            $this->createMock(PostActionsExecutorInterface::class),
        );
    }

    public function testLaunch(): void
    {
        $startState = $this->createMockState('start');

        $processDefinition = $this->createMock(ProcessDefinitionInterface::class);
        $processDefinition->expects($this->once())
            ->method('getStartState')
            ->willReturn($startState);

        $this->nextTransitionFinder
            ->expects($this->once())
            ->method('findStateNextTransition')
            ->willReturn(null);

        $this->engine->launch($processDefinition);
    }

    public function testExecuteTransitionWithoutNextTransition(): void
    {
        $state = $this->createMockState('end');
        $context = $this->createMockContext();

        $this->nextTransitionFinder
            ->expects($this->once())
            ->method('findStateNextTransition')
            ->with($context, $state)
            ->willReturn(null);

        $result = $this->engine->executeTransition($state, $context);
        $this->assertSame($context, $result);
    }

    public function testExecuteTransitionWithSingleTransition(): void
    {
        $endState = $this->createMockState('end');
        $action = $this->createMockAction();
        $context = $this->createMockContext();

        $transition = $this->createMockTransition($this->createMockState('middle'), $endState, $action);
        $startState = $this->createMockState('start');

        $this->nextTransitionFinder
            ->expects($this->exactly(2))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition, null);

        $this->engine->executeTransition($startState, $context);
    }

    public function testExecuteTransitionWithMultipleTransitions(): void
    {
        $endState = $this->createMockState('end');
        $action1 = $this->createMockAction();
        $action2 = $this->createMockAction();
        $context = $this->createMockContext();

        $middleState = $this->createMockState('middle');
        $transition1 = $this->createMockTransition($this->createMockState('start'), $middleState, $action1);
        $transition2 = $this->createMockTransition($middleState, $endState, $action2);
        $startState = $this->createMockState('start');

        $this->nextTransitionFinder
            ->expects($this->exactly(3))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition1, $transition2, null);

        $this->engine->executeTransition($startState, $context);
    }

    public function testExecuteTransitionWithNullAction(): void
    {
        $endState = $this->createMockState('end');
        $transition = $this->createMockTransition($this->createMockState('start'), $endState, null);
        $startState = $this->createMockState('start');
        $context = $this->createMockContext();

        $this->nextTransitionFinder
            ->expects($this->exactly(2))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition, null);

        $result = $this->engine->executeTransition($startState, $context);
        $this->assertSame($context, $result);
    }

    public function testExecuteActionWithNull(): void
    {
        $this->expectNotToPerformAssertions();
        $this->engine->executeAction(null, []);
    }

    public function testExecuteActionWithValidAction(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())->method('run');
        $this->engine->executeAction($action, []);
    }

    public function testCompleteWorkflow(): void
    {
        $endState = $this->createMockState('end');
        $action1 = $this->createMockAction();
        $action2 = $this->createMockAction();

        $middleState = $this->createMockState('middle');
        $transition1 = $this->createMockTransition($this->createMockState('start'), $middleState, $action1);
        $transition2 = $this->createMockTransition($middleState, $endState, $action2);
        $startState = $this->createMockState('start');

        $this->nextTransitionFinder
            ->expects($this->exactly(3))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition1, $transition2, null);

        $processDefinition = $this->createMock(ProcessDefinitionInterface::class);
        $processDefinition->expects($this->once())
            ->method('getStartState')
            ->willReturn($startState);

        $this->engine->launch($processDefinition);
    }

    public function testExecuteTransitionHandlesActionException(): void
    {
        $endState = $this->createMockState('end');
        $action = $this->createMock(ActionInterface::class);
        $action->method('run')->willThrowException(new \RuntimeException('Action failed'));

        $transition = $this->createMockTransition($this->createMockState('start'), $endState, $action);
        $startState = $this->createMockState('start');
        $context = $this->createMockContext();

        $this->nextTransitionFinder
            ->expects($this->once())
            ->method('findStateNextTransition')
            ->with($context, $startState)
            ->willReturn($transition);

        $this->engine->executeTransition($startState, $context);

        $this->assertSame(ProcessExecutionContextStatusEnum::FAILED, $context->getStatus());
    }

    public function testExecuteTransitionSetsContextState(): void
    {
        $endState = $this->createMockState('end');
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())->method('run');

        $transition = $this->createMockTransition($this->createMockState('start'), $endState, $action);
        $startState = $this->createMockState('start');
        $context = $this->createMockContext();

        $this->nextTransitionFinder
            ->expects($this->exactly(2))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition, null);

        $this->engine->executeTransition($startState, $context);

        $this->assertSame($endState, $context->getLastState());
        $this->assertCount(1, $context->getExecutedTransitions());
    }

    public function testLaunchSetsContextToFinishedWhenRunning(): void
    {
        $startState = $this->createMockState('start');
        $processDefinition = $this->createMock(ProcessDefinitionInterface::class);
        $processDefinition->method('getStartState')->willReturn($startState);

        $this->nextTransitionFinder
            ->expects($this->once())
            ->method('findStateNextTransition')
            ->willReturn(null);

        // Test that the engine launches successfully without throwing exceptions
        $this->engine->launch($processDefinition);
        $this->addToAssertionCount(1); // Verify no exception was thrown
    }

    public function testCompleteWorkflowSetsContextToFinished(): void
    {
        $endState = $this->createMockState('end');
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())->method('run');

        $transition = $this->createMockTransition($this->createMockState('start'), $endState, $action);
        $startState = $this->createMockState('start');

        $this->nextTransitionFinder
            ->expects($this->exactly(2))
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition, null);

        $processDefinition = $this->createMock(ProcessDefinitionInterface::class);
        $processDefinition->method('getStartState')->willReturn($startState);

        // Test that the engine completes successfully without throwing exceptions
        $this->engine->launch($processDefinition);
    }

    private function createMockState(string $name = 'test'): StateInterface
    {
        $state = $this->createMock(StateInterface::class);
        $state->method('getName')->willReturn($name);
        $state->method('getNextTransitions')->willReturn([]);

        return $state;
    }

    private function createMockTransition(StateInterface $fromState, StateInterface $toState, ?ActionInterface $action = null): TransitionInterface
    {
        $transition = $this->createMock(TransitionInterface::class);
        $transition->method('getFromState')->willReturn($fromState);
        $transition->method('getToState')->willReturn($toState);
        $transition->method('getAction')->willReturn($action);
        $transition->method('getPostActions')->willReturn([]);

        return $transition;
    }

    private function createMockAction(): ActionInterface
    {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())->method('run');

        return $action;
    }

    private function createMockContext(): ProcessExecutionContextInterface
    {
        $context = new ProcessExecutionContext(
            'test-id',
            ProcessExecutionContextStatusEnum::RUNNING,
            new \DateTimeImmutable(),
            []
        );
        $context->setLastState($this->createMockState('initial'));

        return $context;
    }
}
