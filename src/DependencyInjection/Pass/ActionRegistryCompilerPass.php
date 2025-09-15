<?php

declare(strict_types=1);

namespace App\DependencyInjection\Pass;

use App\StateMachine\Action\ActionInterface;
use App\StateMachine\Action\ActionRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ActionRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ActionRegistryInterface::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(ActionRegistryInterface::class);
        $taggedServices = $container->findTaggedServiceIds('workflow.action');
        $registeredClasses = [];

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            if (null === $class) {
                $class = $id;
            }

            // Check for duplicate class registrations
            if (isset($registeredClasses[$class])) {
                throw new \LogicException(sprintf(
                    'Action class "%s" is already registered by service "%s", cannot register again with service "%s"',
                    $class,
                    $registeredClasses[$class],
                    $id
                ));
            }

            // Validate that the class implements ActionInterface
            if (class_exists($class)) {
                $reflectionClass = new \ReflectionClass($class);
                if (!$reflectionClass->implementsInterface(ActionInterface::class)) {
                    throw new \LogicException(sprintf(
                        'Service "%s" with class "%s" is tagged as workflow.action but does not implement ActionInterface',
                        $id,
                        $class
                    ));
                }
            }

            $registeredClasses[$class] = $id;
            $registryDefinition->addMethodCall('register', [$class, new Reference($id)]);
        }
    }
}
