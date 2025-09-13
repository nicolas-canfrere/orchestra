<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

use App\StateMachine\Contract\StateInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;

interface NextTransitionFinderInterface
{
    public function findStateNextTransition(
        ProcessExecutionContextInterface $context,
        ?StateInterface $state,
    ): ?TransitionInterface;
}
