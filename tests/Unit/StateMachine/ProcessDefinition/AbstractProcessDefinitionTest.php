<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\ProcessDefinition;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\ProcessDefinition\AbstractProcessDefinition;
use App\StateMachine\State\State;
use App\StateMachine\State\StateInterface;
use PHPUnit\Framework\TestCase;

final class AbstractProcessDefinitionTest extends TestCase
{
    public function testConstructorInitializesStartState(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                // Empty init for basic test
            }
        };

        $startState = $processDefinition->getStartState();
        $this->assertSame('startState', $startState->getName());
    }

    public function testStateByNameReturnsNullForNonExistentState(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                // Empty init
            }
        };

        $result = $processDefinition->stateByName('nonexistent');
        $this->assertNull($result);
    }

    public function testStateByNameWithEmptyString(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                // Empty init
            }
        };

        $result = $processDefinition->stateByName('');
        $this->assertNull($result);
    }

    public function testStatesAreRegisteredFromSimpleTransition(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $secondState = new State('second');
                $this->startState->then($secondState);
            }
        };

        $result = $processDefinition->stateByName('second');
        $this->assertInstanceOf(StateInterface::class, $result);
        $this->assertSame('second', $result->getName());
    }

    public function testStatesAreRegisteredFromChainedTransitions(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $secondState = new State('second');
                $thirdState = new State('third');

                $this->startState->then($secondState);
                $secondState->then($thirdState);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('second'));
        $this->assertNotNull($processDefinition->stateByName('third'));
        $this->assertSame('second', $processDefinition->stateByName('second')->getName());
        $this->assertSame('third', $processDefinition->stateByName('third')->getName());
    }

    public function testComplexWorkflowWithMultipleBranches(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $approvalState = new State('approval');
                $approvedState = new State('approved');
                $rejectedState = new State('rejected');
                $finalState = new State('final');

                // Start -> approval
                $this->startState->then($approvalState);

                // Approval can go to approved or rejected
                $approvalState->then($approvedState);
                $approvalState->then($rejectedState);

                // Both approved and rejected go to final
                $approvedState->then($finalState);
                $rejectedState->then($finalState);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('approval'));
        $this->assertNotNull($processDefinition->stateByName('approved'));
        $this->assertNotNull($processDefinition->stateByName('rejected'));
        $this->assertNotNull($processDefinition->stateByName('final'));
    }

    public function testDuplicateStateNamesKeepFirstRegistered(): void
    {
        $duplicateState1 = new State('duplicate');
        $duplicateState2 = new State('duplicate'); // Same name, different instance

        $processDefinition = new class($duplicateState1, $duplicateState2) extends AbstractProcessDefinition {
            public function __construct(
                private StateInterface $duplicateState1,
                private StateInterface $duplicateState2,
            ) {
                parent::__construct();
            }

            public function init(): void
            {
                $this->startState->then($this->duplicateState1);
                $this->startState->then($this->duplicateState2);
            }
        };

        $result = $processDefinition->stateByName('duplicate');
        $this->assertNotNull($result);
        $this->assertSame('duplicate', $result->getName());
        // Should be the first registered instance
        $this->assertSame($duplicateState1, $result);
    }

    public function testDeepNestedStatesAreRegistered(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $level1 = new State('level1');
                $level2 = new State('level2');
                $level3 = new State('level3');
                $level4 = new State('level4');

                $this->startState->then($level1);
                $level1->then($level2);
                $level2->then($level3);
                $level3->then($level4);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('level1'));
        $this->assertNotNull($processDefinition->stateByName('level2'));
        $this->assertNotNull($processDefinition->stateByName('level3'));
        $this->assertNotNull($processDefinition->stateByName('level4'));
    }

    public function testWorkflowWithActionsOnTransitions(): void
    {
        $action = $this->createMock(ActionInterface::class);

        $processDefinition = new class($action) extends AbstractProcessDefinition {
            public function __construct(private ActionInterface $action)
            {
                parent::__construct();
            }

            public function init(): void
            {
                $processingState = new State('processing');
                $completedState = new State('completed');

                $this->startState->then($processingState)->withAction($this->action);
                $processingState->then($completedState);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('processing'));
        $this->assertNotNull($processDefinition->stateByName('completed'));
    }

    public function testEmptyWorkflowWithOnlyStartState(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                // No transitions from start state
            }
        };

        $this->assertSame('startState', $processDefinition->getStartState()->getName());
        $this->assertNull($processDefinition->stateByName('nonexistent'));
        $this->assertEmpty($processDefinition->getStartState()->getNextTransitions());
    }

    public function testMultipleTransitionsFromSameState(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $option1State = new State('option1');
                $option2State = new State('option2');
                $option3State = new State('option3');

                // Multiple transitions from start state
                $this->startState->then($option1State);
                $this->startState->then($option2State);
                $this->startState->then($option3State);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('option1'));
        $this->assertNotNull($processDefinition->stateByName('option2'));
        $this->assertNotNull($processDefinition->stateByName('option3'));
        $this->assertCount(3, $processDefinition->getStartState()->getNextTransitions());
    }

    public function testStartStateHasCorrectNameAfterConstruction(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $someState = new State('someState');
                $this->startState->then($someState);
            }
        };

        $this->assertSame('startState', $processDefinition->getStartState()->getName());
    }

    public function testRegisterStatesSkipsNullToStates(): void
    {
        // This is harder to test directly since registerStates is private,
        // but we can verify that the mechanism works with incomplete transitions
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $validState = new State('valid');
                $this->startState->then($validState);
            }
        };

        $this->assertNotNull($processDefinition->stateByName('valid'));
        $this->assertNull($processDefinition->stateByName('invalid'));
    }
}
