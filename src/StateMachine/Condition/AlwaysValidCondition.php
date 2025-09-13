<?php

declare(strict_types=1);

namespace App\StateMachine\Condition;

use App\StateMachine\Contract\ProcessExecutionContextInterface;

final class AlwaysValidCondition implements ConditionInterface
{
    public function isValid(ProcessExecutionContextInterface $context): bool
    {
        return true;
    }
}
