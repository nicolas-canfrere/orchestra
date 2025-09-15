<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\ProcessDefinition;

use App\StateMachine\Action\ActionRegistry;
use App\StateMachine\ProcessDefinition\AbstractProcessDefinition;
use App\StateMachine\State\State;
use App\Tests\Unit\StateMachine\Action\TestAction;
use PHPUnit\Framework\TestCase;

final class ActionRegistryIntegrationTest extends TestCase
{
    public function testProcessDefinitionCanUseActionRegistry(): void
    {
        $registry = new ActionRegistry();
        $testAction = new TestAction();
        $registry->register(TestAction::class, $testAction);

        $processDefinition = new class($registry) extends AbstractProcessDefinition {
            public function init(): void
            {
                $state1 = new State('state1');
                $state2 = new State('state2');

                $action = $this->getAction(TestAction::class);
                if (null !== $action) {
                    $this->startState->then($state1)->withAction($action);
                }
                $state1->then($state2);
            }

            public function testHasAction(string $actionClass): bool
            {
                return $this->hasAction($actionClass);
            }

            public function testGetAction(string $actionClass): ?\App\StateMachine\Action\ActionInterface
            {
                return $this->getAction($actionClass);
            }
        };

        // Test that the action is accessible
        $this->assertTrue($processDefinition->testHasAction(TestAction::class));
        $this->assertSame($testAction, $processDefinition->testGetAction(TestAction::class));
        $this->assertFalse($processDefinition->testHasAction('NonExistentAction'));
        $this->assertNull($processDefinition->testGetAction('NonExistentAction'));
    }

    public function testProcessDefinitionWorksWithoutActionRegistry(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $state1 = new State('state1');
                $state2 = new State('state2');

                $this->startState->then($state1);
                $state1->then($state2);
            }

            public function testHasAction(string $actionClass): bool
            {
                return $this->hasAction($actionClass);
            }

            public function testGetAction(string $actionClass): ?\App\StateMachine\Action\ActionInterface
            {
                return $this->getAction($actionClass);
            }
        };

        // Test that methods return appropriate defaults when no registry is provided
        $this->assertFalse($processDefinition->testHasAction(TestAction::class));
        $this->assertNull($processDefinition->testGetAction(TestAction::class));
    }
}
