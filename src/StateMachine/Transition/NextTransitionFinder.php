<?php

declare(strict_types=1);

namespace App\StateMachine\Transition;

use App\StateMachine\Contract\StateInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextStatusEnum;

final class NextTransitionFinder implements NextTransitionFinderInterface
{
    public function findStateNextTransition(
        ProcessExecutionContextInterface $context,
        ?StateInterface $state,
    ): ?TransitionInterface {
        if (!$state) {
            return null;
        }
        if (ProcessExecutionContextStatusEnum::RUNNING !== $context->getStatus()) {
            return null;
        }
        $filtered = array_filter(
            $state->getNextTransitions(),
            function (TransitionInterface $transition) use ($context) {
                return $this->isValid($transition, $context);
            }
        );

        return array_shift($filtered);
    }

    private function isValid(TransitionInterface $transition, ProcessExecutionContextInterface $context): bool
    {
        if (null === $transition->getToState()) {
            return false;
        }
        foreach ($transition->getConditions() as $condition) {
            if (!$condition->isValid($context)) {
                return false;
            }
        }

        return true;
    }
}
