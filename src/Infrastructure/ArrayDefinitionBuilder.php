<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Infrastructure;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;

use function array_key_exists;
use function call_user_func_array;
use function is_string;

/**
 * @internal Builds object by ArrayDefinition.
 */
final class ArrayDefinitionBuilder
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    /**
     * Get an instance of this class or create it.
     *
     * @return static An instance of this class.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Builds an object as described by array definition.
     *
     * @param ContainerInterface $container Container to resolve dependencies with.
     * @param ContainerInterface|null $referenceContainer Container to resolve references with.
     * @param ArrayDefinition $definition Definition to resolve.
     *
     * @throws NotFoundException When no definition or class was found in the container for a given ID.
     * @throws NotInstantiableException When a class can not be instantiated.
     * @throws InvalidConfigException When definition configuration is not valid.
     */
    public function build(ContainerInterface $container, ?ContainerInterface $referenceContainer, ArrayDefinition $definition): object
    {
        $class = $definition->getClass();
        $dependencies = DefinitionExtractor::getInstance()->fromClassName($class);
        $constructorArguments = $definition->getConstructorArguments();

        $this->injectArguments($dependencies, $constructorArguments);

        $resolved = DefinitionResolver::resolveArray($container, $referenceContainer, $dependencies);

        /** @psalm-suppress MixedMethodCall */
        $object = new $class(...array_values($resolved));

        foreach ($definition->getMethodsAndProperties() as $item) {
            /** @var mixed $value */
            [$type, $name, $value] = $item;
            /** @var mixed */
            $value = DefinitionResolver::resolve($container, $referenceContainer, $value);
            if ($type === ArrayDefinition::TYPE_METHOD) {
                /** @var mixed */
                $setter = call_user_func_array([$object, $name], $value);
                if ($setter instanceof $object) {
                    /** @var object */
                    $object = $setter;
                }
            } elseif ($type === ArrayDefinition::TYPE_PROPERTY) {
                $object->$name = $value;
            }
        }

        return $object;
    }

    /**
     * @psalm-param array<string, DefinitionInterface> $dependencies
     *
     * @throws InvalidConfigException
     */
    private function injectArguments(array &$dependencies, array $arguments): void
    {
        $isIntegerIndexed = $this->isIntegerIndexed($arguments);
        $dependencyIndex = 0;
        $usedArguments = [];
        $isVariadic = false;
        foreach ($dependencies as $key => &$value) {
            if ($value instanceof ParameterDefinition && $value->isVariadic()) {
                $isVariadic = true;
            }
            $index = $isIntegerIndexed ? $dependencyIndex : $key;
            if (array_key_exists($index, $arguments)) {
                $value = DefinitionResolver::ensureResolvable($arguments[$index]);
                $usedArguments[$index] = 1;
            }
            $dependencyIndex++;
        }
        unset($value);
        if ($isVariadic) {
            /** @var mixed $value */
            foreach ($arguments as $index => $value) {
                if (!isset($usedArguments[$index])) {
                    $dependencies[$index] = DefinitionResolver::ensureResolvable($value);
                }
            }
        }
        /** @psalm-var array<string, DefinitionInterface> $dependencies */
    }

    /**
     * @throws InvalidConfigException
     */
    private function isIntegerIndexed(array $arguments): bool
    {
        $hasStringIndex = false;
        $hasIntegerIndex = false;

        foreach ($arguments as $index => $_argument) {
            if (is_string($index)) {
                $hasStringIndex = true;
                if ($hasIntegerIndex) {
                    break;
                }
            } else {
                $hasIntegerIndex = true;
                if ($hasStringIndex) {
                    break;
                }
            }
        }
        if ($hasIntegerIndex && $hasStringIndex) {
            throw new InvalidConfigException(
                'Arguments indexed both by name and by position are not allowed in the same array.'
            );
        }

        return $hasIntegerIndex;
    }
}
