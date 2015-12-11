<?php

namespace MooDev\Bounce\Symfony;

use MooDev\Bounce\Config\Bean;
use MooDev\Bounce\Config\ValueProvider;
use MooDev\Bounce\Context\IBeanFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SymfonyConfigBeanFactory implements IBeanFactory
{

    /**
     * Create/retrieve an instance of a named bean.
     * @param string $name
     * @return mixed
     */
    public function createByName($name)
    {
        return new Reference($name);
    }

    /**
     * @return string[] A map of bean names to class names.
     */
    public function getAllBeanClasses()
    {
        throw new \RuntimeException("Not implemented");
    }

    protected function convertValueProviderToValue($valueProvider) {
        if ($valueProvider instanceof SymfonyAwareValueProvider) {
            return $valueProvider->getSymfonyValue();
        } else {
            /**
             * @var ValueProvider $valueProvider
             */
            return $valueProvider->getValue($this);
        }
    }

    /**
     * @param Bean $bean
     * @return mixed
     */
    public function create(Bean $bean)
    {
        if ($bean->factoryMethod) {
            // We don't have a clue what what the real class is, fake it and hope nothing breaks;
            $class = "stdClass";
        } else {
            $class = ltrim($bean->class, '\\');
        }

        $def = new Definition($class);

        if ($bean->scope) {
            switch ($bean->scope) {
                case "singleton":
                    $def->setScope(ContainerBuilder::SCOPE_CONTAINER);
                    break;
                case "prototype":
                    $def->setScope(ContainerBuilder::SCOPE_PROTOTYPE);
                    break;
                default:
                    $def->setScope($bean->scope);
            }
        }

        foreach ($bean->constructorArguments as $constructorArgument) {
            $def->addArgument($this->convertValueProviderToValue($constructorArgument));
        }

        foreach ($bean->properties as $name => $property) {
            $def->setProperty($name, $this->convertValueProviderToValue($property));
        }

        if ($bean->factoryBean) {
            $def->setFactory([new Reference($bean->factoryBean), $bean->factoryMethod]);
        } elseif ($bean->factoryMethod) {
            $def->setFactory([$class, $bean->factoryMethod]);
        }

        return $def;
    }
}