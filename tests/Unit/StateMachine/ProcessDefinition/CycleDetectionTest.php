<?php

declare(strict_types=1);

namespace App\Tests\Unit\StateMachine\ProcessDefinition;

use App\StateMachine\ProcessDefinition\AbstractProcessDefinition;
use App\StateMachine\ProcessDefinition\CycleDetectedException;
use App\StateMachine\State\State;
use PHPUnit\Framework\TestCase;

final class CycleDetectionTest extends TestCase
{
    public function testNoCycleDetectedInLinearWorkflow(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');
                $stateB = new State('stateB');
                $stateC = new State('stateC');

                $this->startState->then($stateA);
                $stateA->then($stateB);
                $stateB->then($stateC);
            }
        };

        $result = $processDefinition->validateCycleDetection();
        $this->assertNull($result);
    }

    public function testNoCycleDetectedInBranchingWorkflow(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $approval = new State('approval');
                $approved = new State('approved');
                $rejected = new State('rejected');
                $final = new State('final');

                $this->startState->then($approval);
                $approval->then($approved);
                $approval->then($rejected);
                $approved->then($final);
                $rejected->then($final);
            }
        };

        $result = $processDefinition->validateCycleDetection();
        $this->assertNull($result);
    }

    public function testSimpleCycleDetected(): void
    {
        $this->expectException(CycleDetectedException::class);
        $this->expectExceptionMessage('Cycle detected in process definition: stateA -> stateB -> stateA');

        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');
                $stateB = new State('stateB');

                $this->startState->then($stateA);
                $stateA->then($stateB);
                $stateB->then($stateA); // Creates cycle: A -> B -> A
            }
        };
    }

    public function testComplexCycleDetected(): void
    {
        $this->expectException(CycleDetectedException::class);
        $this->expectExceptionMessage('Cycle detected in process definition: stateA -> stateB -> stateC -> stateA');

        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');
                $stateB = new State('stateB');
                $stateC = new State('stateC');

                $this->startState->then($stateA);
                $stateA->then($stateB);
                $stateB->then($stateC);
                $stateC->then($stateA); // Creates cycle: A -> B -> C -> A
            }
        };
    }

    public function testSelfLoopCycleDetected(): void
    {
        $this->expectException(CycleDetectedException::class);
        $this->expectExceptionMessage('Cycle detected in process definition: stateA -> stateA');

        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');

                $this->startState->then($stateA);
                $stateA->then($stateA); // Creates self-loop
            }
        };
    }

    public function testCycleWithStartStateDetected(): void
    {
        $this->expectException(CycleDetectedException::class);
        $this->expectExceptionMessage('Cycle detected in process definition: startState -> stateA -> startState');

        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');

                $this->startState->then($stateA);
                $stateA->then($this->startState); // Creates cycle back to start
            }
        };
    }

    public function testValidateCycleDetectionReturnsCorrectPath(): void
    {
        $this->expectException(CycleDetectedException::class);
        $this->expectExceptionMessage(
            'Cycle detected in process definition: stateA -> stateB -> stateC -> stateA'
        );
        $processDefinition = new class extends AbstractProcessDefinition {
            public function __construct()
            {
                parent::__construct(); // Disable automatic detection in constructor
            }

            public function init(): void
            {
                $stateA = new State('stateA');
                $stateB = new State('stateB');
                $stateC = new State('stateC');

                $this->startState->then($stateA);
                $stateA->then($stateB);
                $stateB->then($stateC);
                $stateC->then($stateA); // Creates cycle: A -> B -> C -> A
            }
        };
    }

    public function testCycleDetectionWithComplexBranchingAndCycle(): void
    {
        $this->expectException(CycleDetectedException::class);

        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $approval = new State('approval');
                $approved = new State('approved');
                $rejected = new State('rejected');
                $review = new State('review');

                $this->startState->then($approval);
                $approval->then($approved);
                $approval->then($rejected);
                $rejected->then($review);
                $review->then($approval); // Creates cycle: approval -> rejected -> review -> approval
            }
        };
    }

    public function testNoCycleInDisconnectedComponents(): void
    {
        $processDefinition = new class extends AbstractProcessDefinition {
            public function init(): void
            {
                $stateA = new State('stateA');
                $stateB = new State('stateB');
                $stateC = new State('stateC');

                // Linear chain: start -> A -> B
                $this->startState->then($stateA);
                $stateA->then($stateB);

                // Disconnected state C (no cycle)
                // Note: In practice, stateC won't be reachable from start, but it should not create a cycle
            }
        };

        $result = $processDefinition->validateCycleDetection();
        $this->assertNull($result);
    }
}
