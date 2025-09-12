<?php

declare(strict_types=1);

namespace App\StateMachine\Contract;

interface TransitionInterface
{
    public function getFromState(): StateInterface;

    public function getToState(): StateInterface;
}
