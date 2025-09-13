<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

use App\StateMachine\Action\ActionInterface;

interface EngineInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function launch(ProcessDefinitionInterface $processDefinition, array $parameters = []): void;

    public function executeTransition(
        StateInterface $currentState,
        ProcessExecutionContextInterface $context,
    ): ProcessExecutionContextInterface;

    /**
     * @param array<string, mixed> $parameters
     */
    public function executeAction(?ActionInterface $action, array $parameters): void;
}
