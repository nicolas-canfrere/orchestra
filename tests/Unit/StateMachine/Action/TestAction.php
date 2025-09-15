<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\Action;

use App\StateMachine\Action\ActionInterface;

final class TestAction implements ActionInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function run(array $parameters): void
    {
        // Test action implementation
    }
}
