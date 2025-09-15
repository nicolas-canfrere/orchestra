<?php

declare(strict_types=1);

namespace App\StateMachine\ProcessDefinition;

use App\StateMachine\State\State;
use App\StateMachine\State\StateInterface;
use App\StateMachine\Transition\TransitionInterface;

abstract class AbstractProcessDefinition implements ProcessDefinitionInterface
{
    /**
     * @var array<string, StateInterface>
     */
    protected array $states = [];
    protected StateInterface $startState;

    public function __construct()
    {
        $this->startState = new State('startState');
        $this->init();
        $this->registerStates($this->startState->getNextTransitions());
    }

    abstract public function init(): void;

    public function getStartState(): StateInterface
    {
        return $this->startState;
    }

    public function stateByName(string $stateName): ?StateInterface
    {
        return array_key_exists($stateName, $this->states) ? $this->states[$stateName] : null;
    }

    /**
     * @param TransitionInterface[] $transitions
     */
    private function registerStates(array $transitions): void
    {
        foreach ($transitions as $transition) {
            $toState = $transition->getToState();
            if (null === $toState) {
                continue;
            }
            if (array_key_exists($toState->getName(), $this->states)) {
                continue;
            }
            $this->states[$toState->getName()] = $toState;
            if (!empty($toState->getNextTransitions())) {
                $this->registerStates($toState->getNextTransitions());
            }
        }
    }
}
