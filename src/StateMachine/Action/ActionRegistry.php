<?php

declare(strict_types=1);

namespace App\StateMachine\Action;

final class ActionRegistry implements ActionRegistryInterface
{
    /**
     * @var array<string, ActionInterface>
     */
    private array $actions = [];

    public function register(string $name, ActionInterface $action): void
    {
        if (isset($this->actions[$name])) {
            throw new ActionAlreadyRegisteredException(sprintf('Action with name "%s" is already registered', $name));
        }

        $this->actions[$name] = $action;
    }

    public function get(string $name): ?ActionInterface
    {
        return $this->actions[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->actions[$name]);
    }

    /**
     * @return array<string, ActionInterface>
     */
    public function getAll(): array
    {
        return [...$this->actions];
    }
}
