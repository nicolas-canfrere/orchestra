<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface ProcessDefinitionInterface
{
    public function init(): void;

    public function getStartState(): StateInterface;
}
