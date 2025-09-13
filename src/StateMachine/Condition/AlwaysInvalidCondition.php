<?php

declare(strict_types=1);

namespace App\StateMachine\Condition;

use App\StateMachine\Condition\ConditionInterface;
use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;

final class AlwaysInvalidCondition implements ConditionInterface
{

    public function isValid(ProcessExecutionContextInterface $context): bool
    {
        return false;
    }
}
