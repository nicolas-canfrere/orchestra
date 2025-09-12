<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface ActionInterface
{
    public function run(): void;
}
