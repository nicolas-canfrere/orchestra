<?php

declare(strict_types=1);

namespace App\StateMachine\Action;

interface PostActionInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function run(array $parameters): void;
}
