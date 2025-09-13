<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\State\State;
use PHPUnit\Framework\TestCase;

final class StateTest extends TestCase
{
    public function testConstructorSetsName(): void
    {
        $stateName = 'initial';
        $state = new State($stateName);

        $this->assertSame($stateName, $state->getName());
    }

    public function testGetNextTransitionsInitiallyReturnsEmptyArray(): void
    {
        $state = new State('test');

        $this->assertSame([], $state->getNextTransitions());
    }

    public function testThenCreatesTransitionToTargetState(): void
    {
        $fromState = new State('from');
        $toState = new State('to');

        $transition = $fromState->then($toState);

        $this->assertSame($fromState, $transition->getFromState());
        $this->assertSame($toState, $transition->getToState());
    }

    public function testThenAddsTransitionToNextTransitions(): void
    {
        $fromState = new State('from');
        $toState = new State('to');

        $transition = $fromState->then($toState);

        $nextTransitions = $fromState->getNextTransitions();
        $this->assertCount(1, $nextTransitions);
        $this->assertSame($transition, $nextTransitions[0]);
    }

    public function testThenAllowsMultipleTransitions(): void
    {
        $fromState = new State('from');
        $firstToState = new State('first');
        $secondToState = new State('second');

        $firstTransition = $fromState->then($firstToState);
        $secondTransition = $fromState->then($secondToState);

        $nextTransitions = $fromState->getNextTransitions();
        $this->assertCount(2, $nextTransitions);
        $this->assertContains($firstTransition, $nextTransitions);
        $this->assertContains($secondTransition, $nextTransitions);
    }

    public function testStateCanTransitionToItself(): void
    {
        $state = new State('self');

        $transition = $state->then($state);

        $this->assertSame($state, $transition->getFromState());
        $this->assertSame($state, $transition->getToState());
    }

    public function testMultipleStatesWithSameName(): void
    {
        $state1 = new State('duplicate');
        $state2 = new State('duplicate');

        $this->assertSame('duplicate', $state1->getName());
        $this->assertSame('duplicate', $state2->getName());
        $this->assertNotSame($state1, $state2);
    }

    public function testEmptyStateName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('State name cannot be empty.');
        new State('');
    }
}
