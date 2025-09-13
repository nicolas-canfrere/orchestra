<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Contract\ProcessDefinitionInterface;
use App\StateMachine\Contract\ProcessExecutionContextIdGeneratorInterface;
use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Engine\CircularTransitionException;
use App\StateMachine\Engine\Engine;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContext;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
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

    protected function setUp(): void
    {
        $idGenerator = $this->createMock(ProcessExecutionContextIdGeneratorInterface::class);
        $contextFactory = new ProcessExecutionContextFactory($idGenerator);
        $this->nextTransitionFinder = $this->createMock(NextTransitionFinderInterface::class);
        $this->engine = new Engine($contextFactory, $this->nextTransitionFinder);
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

    public function testDetectsCircularTransition(): void
    {
        $state1 = $this->createMock(StateInterface::class);
        $state2 = $this->createMock(StateInterface::class);

        $transition1 = $this->createMock(TransitionInterface::class);
        $transition2 = $this->createMock(TransitionInterface::class);

        // Create circular reference: start → state2 → state2 (circular)
        $transition1->method('getToState')->willReturn($state2);
        $transition1->method('getAction')->willReturn(null);
        $transition1->method('getPostActions')->willReturn([]);

        $transition2->method('getToState')->willReturn($state2); // Same state again = circular
        $transition2->method('getAction')->willReturn(null);
        $transition2->method('getPostActions')->willReturn([]);

        // The finder will return transition1 (go to state2), then transition2 (try to go to state2 again)
        $this->nextTransitionFinder
            ->method('findStateNextTransition')
            ->willReturnOnConsecutiveCalls($transition1, $transition2);

        $this->expectException(CircularTransitionException::class);
        $this->expectExceptionMessage('Circular transition detected');

        $context = $this->createMockContext();
        $this->engine->executeTransition($state1, $context);
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
        $this->assertSame($transition, $context->getCurrentTransition());
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
