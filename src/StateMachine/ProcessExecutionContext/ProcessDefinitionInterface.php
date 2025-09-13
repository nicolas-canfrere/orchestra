<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\State\StateInterface;

interface ProcessDefinitionInterface
{
    public function init(): void;

    public function getStartState(): StateInterface;
}
