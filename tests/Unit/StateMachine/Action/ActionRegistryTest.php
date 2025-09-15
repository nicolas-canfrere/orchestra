<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\Action;

use App\StateMachine\Action\ActionAlreadyRegisteredException;
use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\ActionRegistry;
use PHPUnit\Framework\TestCase;

final class ActionRegistryTest extends TestCase
{
    private ActionRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ActionRegistry();
    }

    public function testRegisterAction(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $this->registry->register('TestAction', $action);

        $this->assertTrue($this->registry->has('TestAction'));
        $this->assertSame($action, $this->registry->get('TestAction'));
    }

    public function testRegisterDuplicateActionThrowsException(): void
    {
        $action1 = $this->createMock(ActionInterface::class);
        $action2 = $this->createMock(ActionInterface::class);

        $this->registry->register('TestAction', $action1);

        $this->expectException(ActionAlreadyRegisteredException::class);
        $this->expectExceptionMessage('Action with name "TestAction" is already registered');

        $this->registry->register('TestAction', $action2);
    }

    public function testGetNonExistentActionReturnsNull(): void
    {
        $this->assertNull($this->registry->get('NonExistentAction'));
    }

    public function testHasReturnsFalseForNonExistentAction(): void
    {
        $this->assertFalse($this->registry->has('NonExistentAction'));
    }

    public function testGetAllReturnsAllRegisteredActions(): void
    {
        $action1 = $this->createMock(ActionInterface::class);
        $action2 = $this->createMock(ActionInterface::class);

        $this->registry->register('Action1', $action1);
        $this->registry->register('Action2', $action2);

        $allActions = $this->registry->getAll();

        $this->assertCount(2, $allActions);
        $this->assertSame($action1, $allActions['Action1']);
        $this->assertSame($action2, $allActions['Action2']);
    }

    public function testGetAllReturnsEmptyArrayWhenNoActionsRegistered(): void
    {
        $this->assertSame([], $this->registry->getAll());
    }
}
