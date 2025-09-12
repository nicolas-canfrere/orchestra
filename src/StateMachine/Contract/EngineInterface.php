<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface EngineInterface
{
    public function launch(ProcessDefinitionInterface $processDefinition): void;

    public function executeTransition(StateInterface $currentState): void;

    public function executeAction(?ActionInterface $action): void;
}
