<?php

declare(strict_types=1);

namespace App\ProcessDefinition;

use App\StateMachine\ProcessDefinition\AbstractProcessDefinition;
use App\StateMachine\State\State;

final class ExampleProcessDefinition extends AbstractProcessDefinition
{
    public function init(): void
    {
        $state1 = new State('state1');
        $state2 = new State('state2');
        $state3 = new State('state3');

        $this->startState->then($state1);
        $state1->then($state2);
        $state2->then($state3);
    }
}
