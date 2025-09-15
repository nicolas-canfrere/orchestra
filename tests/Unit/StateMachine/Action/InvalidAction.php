<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\Action;

final class InvalidAction
{
    public function notRun(): void
    {
        // This class does not implement ActionInterface
    }
}
