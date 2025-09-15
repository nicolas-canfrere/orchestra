<?php

declare(strict_types=1);

namespace App\ProcessDefinition;

use App\StateMachine\Condition\AlwaysInvalidCondition;
use App\StateMachine\Condition\AlwaysValidCondition;
use App\StateMachine\ProcessDefinition\AbstractProcessDefinition;
use App\StateMachine\State\State;

final class Example3ProcessDefinition extends AbstractProcessDefinition
{
    public function init(): void
    {
        $state1 = new State('state1');
        $state2 = new State('state2');
        $state3 = new State('state3');
        $state4 = new State('state4');
        $state5 = new State('state5');

        /*
         * startState
         * state1
         * state2
         * => PAUSED
         */

        $this->startState->then($state1);
        $state1
            ->when([new AlwaysValidCondition()])
            ->then($state2)
            ->withPauseAfterTransition();
        $state1
            ->then($state3);
        $state2
            ->when([new AlwaysInvalidCondition()])
            ->then($state4);
        $state2
            ->then($state5);
    }
}
