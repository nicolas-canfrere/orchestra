<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\Condition\ConditionInterface;
use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;
use App\StateMachine\Transition\NextTransitionFinder;
use App\StateMachine\Transition\TransitionInterface;
use PHPUnit\Framework\TestCase;

final class NextTransitionFinderTest extends TestCase
{
    private NextTransitionFinder $finder;

    protected function setUp(): void
    {
        $this->finder = new NextTransitionFinder();
    }

    public function testFindStateNextTransitionReturnsNullWhenContextNotRunning(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::FINISHED);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertNull($result);
    }

    public function testFindStateNextTransitionReturnsNullWhenNoTransitions(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([]);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertNull($result);
    }

    public function testFindStateNextTransitionReturnsFirstValidTransition(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $transition = $this->createMock(TransitionInterface::class);
        $toState = $this->createMock(StateInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$transition]);

        $transition->method('getConditions')
            ->willReturn([]);
        $transition->method('getToState')
            ->willReturn($toState);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertSame($transition, $result);
    }

    public function testFindStateNextTransitionSkipsInvalidTransitions(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $invalidTransition = $this->createMock(TransitionInterface::class);
        $validTransition = $this->createMock(TransitionInterface::class);
        $invalidCondition = $this->createMock(ConditionInterface::class);
        $toState = $this->createMock(StateInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$invalidTransition, $validTransition]);

        // Invalid transition has a condition that returns false
        $invalidTransition->method('getConditions')
            ->willReturn([$invalidCondition]);
        $invalidTransition->method('getToState')
            ->willReturn($toState);

        $invalidCondition->method('isValid')
            ->with($context)
            ->willReturn(false);

        // Valid transition has no conditions (always valid)
        $validTransition->method('getConditions')
            ->willReturn([]);
        $validTransition->method('getToState')
            ->willReturn($toState);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertSame($validTransition, $result);
    }

    public function testFindStateNextTransitionReturnsNullWhenAllTransitionsInvalid(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $transition1 = $this->createMock(TransitionInterface::class);
        $transition2 = $this->createMock(TransitionInterface::class);
        $invalidCondition1 = $this->createMock(ConditionInterface::class);
        $invalidCondition2 = $this->createMock(ConditionInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$transition1, $transition2]);

        $transition1->method('getConditions')
            ->willReturn([$invalidCondition1]);

        $transition2->method('getConditions')
            ->willReturn([$invalidCondition2]);

        $invalidCondition1->method('isValid')
            ->with($context)
            ->willReturn(false);

        $invalidCondition2->method('isValid')
            ->with($context)
            ->willReturn(false);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertNull($result);
    }

    public function testFindStateNextTransitionWithMultipleConditions(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $transition = $this->createMock(TransitionInterface::class);
        $condition1 = $this->createMock(ConditionInterface::class);
        $condition2 = $this->createMock(ConditionInterface::class);
        $toState = $this->createMock(StateInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$transition]);

        $transition->method('getConditions')
            ->willReturn([$condition1, $condition2]);
        $transition->method('getToState')
            ->willReturn($toState);

        // All conditions must return true for transition to be valid
        $condition1->method('isValid')
            ->with($context)
            ->willReturn(true);

        $condition2->method('isValid')
            ->with($context)
            ->willReturn(true);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertSame($transition, $result);
    }

    public function testFindStateNextTransitionFailsWhenOneConditionInvalid(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $transition = $this->createMock(TransitionInterface::class);
        $validCondition = $this->createMock(ConditionInterface::class);
        $invalidCondition = $this->createMock(ConditionInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$transition]);

        $transition->method('getConditions')
            ->willReturn([$validCondition, $invalidCondition]);

        $validCondition->method('isValid')
            ->with($context)
            ->willReturn(true);

        // One invalid condition makes the whole transition invalid
        $invalidCondition->method('isValid')
            ->with($context)
            ->willReturn(false);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertNull($result);
    }

    public function testFindStateNextTransitionHandlesDifferentContextStatuses(): void
    {
        $state = $this->createMock(StateInterface::class);

        // Test with PAUSED status
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::PAUSED);

        $result = $this->finder->findStateNextTransition($context, $state);
        $this->assertNull($result);

        // Test with FAILED status
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::FAILED);

        $result = $this->finder->findStateNextTransition($context, $state);
        $this->assertNull($result);

        // Test with FINISHED status
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::FINISHED);

        $result = $this->finder->findStateNextTransition($context, $state);
        $this->assertNull($result);
    }

    public function testFindStateNextTransitionReturnsNullForTransitionWithoutToState(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $transition = $this->createMock(TransitionInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$transition]);

        // Transition without toState
        $transition->method('getToState')
            ->willReturn(null);
        $transition->method('getConditions')
            ->willReturn([]);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertNull($result);
    }

    public function testFindStateNextTransitionSkipsTransitionsWithoutToState(): void
    {
        $state = $this->createMock(StateInterface::class);
        $context = $this->createMock(ProcessExecutionContextInterface::class);
        $incompleteTransition = $this->createMock(TransitionInterface::class);
        $validTransition = $this->createMock(TransitionInterface::class);
        $toState = $this->createMock(StateInterface::class);

        $context->method('getStatus')
            ->willReturn(ProcessExecutionContextStatusEnum::RUNNING);

        $state->method('getNextTransitions')
            ->willReturn([$incompleteTransition, $validTransition]);

        // Incomplete transition (no toState)
        $incompleteTransition->method('getToState')
            ->willReturn(null);
        $incompleteTransition->method('getConditions')
            ->willReturn([]);

        // Valid transition
        $validTransition->method('getToState')
            ->willReturn($toState);
        $validTransition->method('getConditions')
            ->willReturn([]);

        $result = $this->finder->findStateNextTransition($context, $state);

        $this->assertSame($validTransition, $result);
    }
}
