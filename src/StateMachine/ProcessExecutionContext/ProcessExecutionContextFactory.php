<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

use App\StateMachine\Contract\ProcessExecutionContextIdGeneratorInterface;
use App\StateMachine\Contract\ProcessExecutionContextInterface;
use App\StateMachine\Contract\StateInterface;

final readonly class ProcessExecutionContextFactory
{
    public function __construct(
        private ProcessExecutionContextIdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function create(
        StateInterface $lastState,
        array $parameters,
    ): ProcessExecutionContextInterface {
        $pec = new ProcessExecutionContext(
            $this->idGenerator->generate(),
            ProcessExecutionContextStatusEnum::RUNNING,
            new \DateTimeImmutable(),
            $parameters,
        );
        $pec->setLastState($lastState);

        return $pec;
    }
}
