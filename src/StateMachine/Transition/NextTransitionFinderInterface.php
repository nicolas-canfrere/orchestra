<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;

interface NextTransitionFinderInterface
{
    public function findStateNextTransition(
        ProcessExecutionContextInterface $context,
        ?StateInterface $state,
    ): ?TransitionInterface;
}
