<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface ProcessExecutionContextIdGeneratorInterface
{
    public function generate(): string;
}
