<?php

declare(strict_types=1);

namespace App\Tests\Unit\DependencyInjection\Pass;

use App\DependencyInjection\Pass\ActionRegistryCompilerPass;
use App\StateMachine\Action\ActionRegistryInterface;
use App\Tests\Unit\StateMachine\Action\InvalidAction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ActionRegistryCompilerPassTest extends TestCase
{
    private ActionRegistryCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new ActionRegistryCompilerPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcessDoesNothingWhenRegistryServiceNotExists(): void
    {
        // Add a dummy service to ensure container has definitions but no registry
        $dummyDefinition = new Definition();
        $this->container->setDefinition('dummy_service', $dummyDefinition);

        $this->compilerPass->process($this->container);

        // Registry should not be created and no method calls should be added to dummy service
        $this->assertFalse($this->container->hasDefinition(ActionRegistryInterface::class));
        $this->assertEmpty($dummyDefinition->getMethodCalls());
    }

    public function testProcessRegistersTaggedServices(): void
    {
        // Create registry service
        $registryDefinition = new Definition();
        $this->container->setDefinition(ActionRegistryInterface::class, $registryDefinition);

        // Create tagged services
        $actionDefinition1 = new Definition();
        $actionDefinition1->setClass('App\\Action\\TestAction1');
        $this->container->setDefinition('test_action_1', $actionDefinition1);
        $this->container->getDefinition('test_action_1')->addTag('workflow.action');

        $actionDefinition2 = new Definition();
        $actionDefinition2->setClass('App\\Action\\TestAction2');
        $this->container->setDefinition('test_action_2', $actionDefinition2);
        $this->container->getDefinition('test_action_2')->addTag('workflow.action');

        $this->compilerPass->process($this->container);

        $methodCalls = $registryDefinition->getMethodCalls();
        $this->assertCount(2, $methodCalls);

        $firstCall = $methodCalls[0];
        $this->assertIsArray($firstCall);
        $this->assertSame('register', $firstCall[0]);
        $this->assertIsArray($firstCall[1]);
        $this->assertSame('App\\Action\\TestAction1', $firstCall[1][0]);
        $this->assertInstanceOf(Reference::class, $firstCall[1][1]);
        $this->assertSame('test_action_1', (string) $firstCall[1][1]);

        $secondCall = $methodCalls[1];
        $this->assertIsArray($secondCall);
        $this->assertSame('register', $secondCall[0]);
        $this->assertIsArray($secondCall[1]);
        $this->assertSame('App\\Action\\TestAction2', $secondCall[1][0]);
        $this->assertInstanceOf(Reference::class, $secondCall[1][1]);
        $this->assertSame('test_action_2', (string) $secondCall[1][1]);
    }

    public function testProcessUsesServiceIdAsClassNameWhenClassNotSet(): void
    {
        // Create registry service
        $registryDefinition = new Definition();
        $this->container->setDefinition(ActionRegistryInterface::class, $registryDefinition);

        // Create tagged service without explicit class
        $actionDefinition = new Definition();
        $this->container->setDefinition('App\\Action\\TestAction', $actionDefinition);
        $this->container->getDefinition('App\\Action\\TestAction')->addTag('workflow.action');

        $this->compilerPass->process($this->container);

        $methodCalls = $registryDefinition->getMethodCalls();
        $this->assertCount(1, $methodCalls);

        $call = $methodCalls[0];
        $this->assertIsArray($call);
        $this->assertSame('register', $call[0]);
        $this->assertIsArray($call[1]);
        $this->assertSame('App\\Action\\TestAction', $call[1][0]);
        $this->assertInstanceOf(Reference::class, $call[1][1]);
        $this->assertSame('App\\Action\\TestAction', (string) $call[1][1]);
    }

    public function testProcessThrowsExceptionForDuplicateClassRegistration(): void
    {
        // Create registry service
        $registryDefinition = new Definition();
        $this->container->setDefinition(ActionRegistryInterface::class, $registryDefinition);

        // Create two services with the same class
        $actionDefinition1 = new Definition();
        $actionDefinition1->setClass('App\\Action\\DuplicateAction');
        $this->container->setDefinition('test_action_1', $actionDefinition1);
        $this->container->getDefinition('test_action_1')->addTag('workflow.action');

        $actionDefinition2 = new Definition();
        $actionDefinition2->setClass('App\\Action\\DuplicateAction');
        $this->container->setDefinition('test_action_2', $actionDefinition2);
        $this->container->getDefinition('test_action_2')->addTag('workflow.action');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Action class "App\Action\DuplicateAction" is already registered by service "test_action_1", cannot register again with service "test_action_2"');

        $this->compilerPass->process($this->container);
    }

    public function testProcessThrowsExceptionForInvalidActionInterface(): void
    {
        // Create registry service
        $registryDefinition = new Definition();
        $this->container->setDefinition(ActionRegistryInterface::class, $registryDefinition);

        // Create service that doesn't implement ActionInterface
        $actionDefinition = new Definition();
        $actionDefinition->setClass(InvalidAction::class);
        $this->container->setDefinition('invalid_action', $actionDefinition);
        $this->container->getDefinition('invalid_action')->addTag('workflow.action');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Service "invalid_action" with class "%s" is tagged as workflow.action but does not implement ActionInterface',
            InvalidAction::class
        ));

        $this->compilerPass->process($this->container);
    }
}
