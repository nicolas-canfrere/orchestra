<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface ConditionInterface
{
    public function isValid(ProcessExecutionContextInterface $context): bool;
}
