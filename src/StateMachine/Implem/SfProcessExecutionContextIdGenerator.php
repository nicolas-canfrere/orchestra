<?php

declare(strict_types=1);

namespace App\StateMachine\Implem;

use App\StateMachine\Contract\ProcessExecutionContextIdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class SfProcessExecutionContextIdGenerator implements ProcessExecutionContextIdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
