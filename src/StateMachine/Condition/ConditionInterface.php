<?php

declare(strict_types=1);

namespace App\StateMachine\Condition;

use App\StateMachine\ProcessExecutionContext\ProcessExecutionContextInterface;

interface ConditionInterface
{
    public function isValid(ProcessExecutionContextInterface $context): bool;
}
