<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine;

use App\StateMachine\Contract\ActionInterface;
use App\StateMachine\Contract\StateInterface;
use App\StateMachine\Exception\TransitionActionAlreadyDefined;
use App\StateMachine\State;
use App\StateMachine\Transition;
use PHPUnit\Framework\TestCase;

final class TransitionTest extends TestCase
{
    private StateInterface $fromState;
    private StateInterface $toState;

    protected function setUp(): void
    {
        $this->fromState = new State('from');
        $this->toState = new State('to');
    }

    public function testConstructorSetsFromAndToStates(): void
    {
        $transition = new Transition($this->fromState, $this->toState);

        $this->assertSame($this->fromState, $transition->getFromState());
        $this->assertSame($this->toState, $transition->getToState());
    }

    public function testGetActionInitiallyReturnsNull(): void
    {
        $transition = new Transition($this->fromState, $this->toState);

        $this->assertNull($transition->getAction());
    }

    public function testWithActionSetsAction(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $transition = new Transition($this->fromState, $this->toState);

        $result = $transition->withAction($action);

        $this->assertSame($action, $transition->getAction());
        $this->assertSame($transition, $result); // Fluent interface
    }

    public function testTransitionFromStateToItself(): void
    {
        $state = new State('self');
        $transition = new Transition($state, $state);

        $this->assertSame($state, $transition->getFromState());
        $this->assertSame($state, $transition->getToState());
    }

    public function testTransitionWithDifferentStateInstances(): void
    {
        $fromState = new State('same');
        $toState = new State('same'); // Same name, different instance
        $transition = new Transition($fromState, $toState);

        $this->assertSame($fromState, $transition->getFromState());
        $this->assertSame($toState, $transition->getToState());
        $this->assertNotSame($fromState, $toState);
    }

    public function testActionCanNotBeOverride(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $transition = new Transition($this->fromState, $this->toState);
        $this->expectException(TransitionActionAlreadyDefined::class);

        $transition->withAction($action)->withAction($action);
    }

    public function testMultipleTransitionsWithSameStates(): void
    {
        $transition1 = new Transition($this->fromState, $this->toState);
        $transition2 = new Transition($this->fromState, $this->toState);

        $this->assertNotSame($transition1, $transition2);
        $this->assertSame($this->fromState, $transition1->getFromState());
        $this->assertSame($this->fromState, $transition2->getFromState());
        $this->assertSame($this->toState, $transition1->getToState());
        $this->assertSame($this->toState, $transition2->getToState());
    }
}
