<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency resolver is used to resolve dependencies of an object obtained from container.
 */
interface DependencyResolverInterface extends ContainerInterface
{
    /**
     * Resolve a reference with an ID specified.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface No entry was found for this identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed|object
     *
     * @psalm-suppress InvalidThrow
     */
    public function resolveReference(string $id);

    /**
     * Invoke a callable resolving dependencies based on its signature.
     *
     * @return mixed Invocation result.
     */
    public function invoke(callable $callable);
}