<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessDefinition;

class CycleDetectedException extends \Exception
{
    /**
     * @param string[] $cyclePath
     */
    public function __construct(array $cyclePath)
    {
        $cycleMessage = implode(' -> ', $cyclePath);
        parent::__construct("Cycle detected in process definition: {$cycleMessage}");
    }
}
