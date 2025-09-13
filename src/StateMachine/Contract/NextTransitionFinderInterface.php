<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface NextTransitionFinderInterface
{
    public function findStateNextTransition(
        ProcessExecutionContextInterface $context,
        ?StateInterface $state,
    ): ?TransitionInterface;
}
