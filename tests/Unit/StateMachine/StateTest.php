<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\State;
use PHPUnit\Framework\TestCase;

final class StateTest extends TestCase
{
    public function testConstructorSetsName(): void
    {
        $stateName = 'initial';
        $state = new State($stateName);

        $this->assertSame($stateName, $state->getName());
    }

    public function testGetNextTransitionInitiallyReturnsNull(): void
    {
        $state = new State('test');

        $this->assertNull($state->getNextTransition());
    }

    public function testThenCreatesTransitionToTargetState(): void
    {
        $fromState = new State('from');
        $toState = new State('to');

        $transition = $fromState->then($toState);

        $this->assertSame($fromState, $transition->getFromState());
        $this->assertSame($toState, $transition->getToState());
    }

    public function testThenSetsNextTransition(): void
    {
        $fromState = new State('from');
        $toState = new State('to');

        $transition = $fromState->then($toState);

        $this->assertSame($transition, $fromState->getNextTransition());
    }

    public function testThenOverwritesPreviousTransition(): void
    {
        $fromState = new State('from');
        $firstToState = new State('first');
        $secondToState = new State('second');

        $firstTransition = $fromState->then($firstToState);
        $secondTransition = $fromState->then($secondToState);

        $this->assertNotSame($firstTransition, $secondTransition);
        $this->assertSame($secondTransition, $fromState->getNextTransition());
        $this->assertSame($secondToState, $fromState->getNextTransition()->getToState());
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
