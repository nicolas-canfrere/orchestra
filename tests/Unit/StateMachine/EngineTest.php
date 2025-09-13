<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\Contract\ActionInterface;
use App\StateMachine\Contract\ProcessDefinitionInterface;
use App\StateMachine\Contract\ProcessExecutionContextIdGeneratorInterface;
use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Contract\TransitionInterface;
use App\StateMachine\Engine;
use App\StateMachine\Exception\CircularTransitionException;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContext;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextFactory;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use PHPUnit\Framework\TestCase;

final class EngineTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $idGenerator = $this->createMock(ProcessExecutionContextIdGeneratorInterface::class);
        $contextFactory = new ProcessExecutionContextFactory($idGenerator);
        $this->engine = new Engine($contextFactory);
    }

    public function testLaunch(): void
    {
        $startState = $this->createMockState('start');

        $processDefinition = $this->createMock(ProcessDefinitionInterface::class);
        $processDefinition->expects($this->once())
            ->method('getStartState')
            ->willReturn($startState);

        $this->engine->launch($processDefinition);
    }

    public function testExecuteTransitionWithoutNextTransition(): void
    {
        $state = $this->createMockState('end');
        $context = $this->createMockContext();

        $this->expectNotToPerformAssertions();
        $this->engine->executeTransition($state, $context);
    }

    public function testExecuteTransitionWithSingleTransition(): void
    {
        $endState = $this->createMockState('end');
        $action = $this->createMockAction();
        $context = $this->createMockContext();

        $transition = $this->createMockTransition($this->createMockState('middle'), $endState, $action);
        $startState = $this->createMockState('start', $transition);

        $this->engine->executeTransition($startState, $context);
    }

    public function testExecuteTransitionWithMultipleTransitions(): void
    {
        $endState = $this->createMockState('end');
        $action1 = $this->createMockAction();
        $action2 = $this->createMockAction();
        $context = $this->createMockContext();

        $transition2 = $this->createMockTransition($this->createMockState('middle'), $endState, $action2);
        $middleState = $this->createMockState('middle', $transition2);

        $transition1 = $this->createMockTransition($this->createMockState('start'), $middleState, $action1);
        $startState = $this->createMockState('start', $transition1);

        $this->engine->executeTransition($startState, $context);
    }

    public function testExecuteTransitionWithNullAction(): void
    {
        $endState = $this->createMockState('end');
        $transition = $this->createMockTransition($this->createMockState('start'), $endState, null);
        $startState = $this->createMockState('start', $transition);
        $context = $this->createMockContext();

        $this->expectNotToPerformAssertions();
        $this->engine->executeTransition($startState, $context);
    }

    public function testExecuteActionWithNull(): void
    {
        $this->expectNotToPerformAssertions();
        $this->engine->executeAction(null, []);
    }

    public function testExecuteActionWithValidAction(): void
    {
        $action = $this->createMockAction();
        $this->engine->executeAction($action, []);
    }

    public function testCompleteWorkflow(): void
    {
        $endState = $this->createMockState('end');
        $action1 = $this->createMockAction();
        $action2 = $this->createMockAction();

        $transition2 = $this->createMockTransition($this->createMockState('middle'), $endState, $action2);
        $middleState = $this->createMockState('middle', $transition2);

        $transition1 = $this->createMockTransition($this->createMockState('start'), $middleState, $action1);
        $startState = $this->createMockState('start', $transition1);

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

        // Create circular reference: state1 → state2 → state1
        $state1->method('getNextTransition')->willReturn($transition1);
        $transition1->method('getToState')->willReturn($state2);
        $transition1->method('getAction')->willReturn(null);

        $state2->method('getNextTransition')->willReturn($transition2);
        $transition2->method('getToState')->willReturn($state1);
        $transition2->method('getAction')->willReturn(null);

        $this->expectException(CircularTransitionException::class);
        $this->expectExceptionMessage('Circular transition detected');

        $context = $this->createMockContext();
        $this->engine->executeTransition($state1, $context);
    }

    private function createMockState(string $name = 'test', ?TransitionInterface $nextTransition = null): StateInterface
    {
        $state = $this->createMock(StateInterface::class);
        $state->method('getName')->willReturn($name);
        $state->method('getNextTransition')->willReturn($nextTransition);

        return $state;
    }

    private function createMockTransition(StateInterface $fromState, StateInterface $toState, ?ActionInterface $action = null): TransitionInterface
    {
        $transition = $this->createMock(TransitionInterface::class);
        $transition->method('getFromState')->willReturn($fromState);
        $transition->method('getToState')->willReturn($toState);
        $transition->method('getAction')->willReturn($action);

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
