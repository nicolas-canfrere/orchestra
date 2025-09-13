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

    public function create(
        StateInterface $lastState,
    ): ProcessExecutionContextInterface {
        $pec = new ProcessExecutionContext(
            $this->idGenerator->generate(),
            ProcessExecutionContextStatusEnum::RUNNING,
            new \DateTimeImmutable(),
        );
        $pec->setLastState($lastState);

        return $pec;
    }
}
