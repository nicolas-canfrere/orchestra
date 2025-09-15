<?php

declare(strict_types=1);

namespace App\StateMachine\Action;

interface ActionRegistryInterface
{
    public function register(string $name, ActionInterface $action): void;

    public function get(string $name): ?ActionInterface;

    public function has(string $name): bool;

    /**
     * @return array<string, ActionInterface>
     */
    public function getAll(): array;
}
