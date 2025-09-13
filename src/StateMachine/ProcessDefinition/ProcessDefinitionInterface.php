<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessDefinition;

use App\StateMachine\State\StateInterface;

interface ProcessDefinitionInterface
{
    public function init(): void;

    public function getStartState(): StateInterface;
}
