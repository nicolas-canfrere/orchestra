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

        $cyclePath = $this->validateCycleDetection();
        if (null !== $cyclePath) {
            throw new CycleDetectedException($cyclePath);
        }
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
     * Validates the process definition for cycles and returns the cycle path if found.
     *
     * @return string[]|null The cycle path as an array of state names, or null if no cycle is found
     */
    public function validateCycleDetection(): ?array
    {
        return $this->findCycle();
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

    /**
     * @return string[]|null
     */
    private function findCycle(): ?array
    {
        /** @var array<string, bool> $visited */
        $visited = [];
        /** @var array<string, bool> $recStack */
        $recStack = [];
        /** @var string[] $path */
        $path = [];

        // Build transitions map
        $transitions = $this->buildTransitionsMap();

        foreach (array_keys($transitions) as $stateName) {
            if (!isset($visited[$stateName])) {
                $cyclePath = $this->dfs($stateName, $visited, $recStack, $path, $transitions);
                if (null !== $cyclePath) {
                    return $cyclePath;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, bool> $visited
     * @param array<string, bool> $recStack
     * @param string[] $path
     * @param array<string, string[]> $transitions
     *
     * @return string[]|null
     */
    private function dfs(string $stateName, array &$visited, array &$recStack, array &$path, array $transitions): ?array
    {
        $visited[$stateName] = true;
        $recStack[$stateName] = true;
        $path[] = $stateName;

        foreach ($transitions[$stateName] ?? [] as $nextStateName) {
            if (!isset($visited[$nextStateName])) {
                $cyclePath = $this->dfs($nextStateName, $visited, $recStack, $path, $transitions);
                if (null !== $cyclePath) {
                    return $cyclePath;
                }
            } elseif (!empty($recStack[$nextStateName])) {
                // Cycle found: reconstruct the cycle path
                $cycleStartIndex = array_search($nextStateName, $path, true);
                if (false !== $cycleStartIndex && is_int($cycleStartIndex)) {
                    $cyclePath = array_slice($path, $cycleStartIndex);
                    $cyclePath[] = $nextStateName; // Close the loop

                    return $cyclePath;
                }
            }
        }

        $recStack[$stateName] = false;
        array_pop($path);

        return null;
    }

    /**
     * Builds a transitions map from state names to arrays of target state names.
     *
     * @return array<string, string[]>
     */
    private function buildTransitionsMap(): array
    {
        /** @var array<string, string[]> $transitions */
        $transitions = [];

        // Add start state to the map
        $startStateName = $this->startState->getName();
        $transitions[$startStateName] = [];

        foreach ($this->startState->getNextTransitions() as $transition) {
            $toState = $transition->getToState();
            if (null !== $toState) {
                $transitions[$startStateName][] = $toState->getName();
            }
        }

        // Add all registered states
        foreach ($this->states as $state) {
            $stateName = $state->getName();
            $transitions[$stateName] = [];

            foreach ($state->getNextTransitions() as $transition) {
                $toState = $transition->getToState();
                if (null !== $toState) {
                    $transitions[$stateName][] = $toState->getName();
                }
            }
        }

        return $transitions;
    }
}
