<?php

declare(strict_types=1);

namespace App\ProcessDefinition;

use App\StateMachine\ProcessDefinition\ProcessDefinitionInterface;
use App\StateMachine\State\State;
use App\StateMachine\State\StateInterface;

final class ExampleProcessDefinition implements ProcessDefinitionInterface
{
    private StateInterface $startState;

    public function __construct()
    {
        $this->startState = new State('startState');
        $this->init();
    }

    public function init(): void
    {
        $state1 = new State('state1');
        $state2 = new State('state2');
        $state3 = new State('state3');

        $this->startState->then($state1);
        $state1->then($state2);
        $state2->then($state3);
    }

    public function getStartState(): StateInterface
    {
        return $this->startState;
    }
}
