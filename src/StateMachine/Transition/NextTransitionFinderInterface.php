<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\State\StateInterface;

interface NextTransitionFinderInterface
{
    public function findStateNextTransition(
        ProcessExecutionContextInterface $context,
        ?StateInterface $state,
    ): ?TransitionInterface;
}
