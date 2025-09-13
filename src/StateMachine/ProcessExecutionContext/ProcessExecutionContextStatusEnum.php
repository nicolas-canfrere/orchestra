<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessExecutionContext;

enum ProcessExecutionContextStatusEnum: string
{
    case RUNNING = 'running';
    case PAUSED = 'paused';
    case FAILED = 'failed';
    case FINISHED = 'finished';
}
