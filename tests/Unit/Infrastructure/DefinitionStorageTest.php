<?php

declare(strict_types=1);

namespace Yiisoft\Dfinitions\Infrastructure\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Infrastructure\DefinitionStorage;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithBuiltinTypeWithoutDefault;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithNonExistingSubDependency;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithNonExistingDependency;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithNonResolvableUnionTypes;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithPrivateConstructor;
use Yiisoft\Definitions\Tests\Objects\DefinitionStorage\ServiceWithPrivateConstructorSubDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionStorageTest extends TestCase
{
    public function testExplicitDefinitionIsNotChecked(): void
    {
        $storage = new DefinitionStorage(['existing' => 'anything']);
        $this->assertTrue($storage->has('existing'));
        $this->assertSame([], $storage->getBuildStack());
    }

    public function testNonExistingService(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(\NonExisitng::class));
        $this->assertSame([\NonExisitng::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingDependency::class => 1,
                \NonExisting::class => 1,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithNonExistingSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingSubDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingSubDependency::class => 1,
                ServiceWithNonExistingDependency::class => 1,
                \NonExisting::class => 1,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithPrivateConstructor(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithPrivateConstructor::class));
        $this->assertSame([ServiceWithPrivateConstructor::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithPrivateConstructorSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithPrivateConstructorSubDependency::class));
        $this->assertSame(
            [
                ServiceWithPrivateConstructorSubDependency::class => 1,
                ServiceWithPrivateConstructor::class => 1,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithBuiltInTypeWithoutDefault(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithBuiltinTypeWithoutDefault::class));
        $this->assertSame([ServiceWithBuiltinTypeWithoutDefault::class => 1], $storage->getBuildStack());
    }

    public function testEmptyDelegateContainer(): void
    {
        $container = new SimpleContainer([]);
        $storage = new DefinitionStorage([]);
        $storage->setDelegateContainer($container);
        $this->assertFalse($storage->has(\NonExisitng::class));
        $this->assertSame([\NonExisitng::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingUnionTypes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Union types are supported by PHP 8+ only.');
        }

        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonResolvableUnionTypes::class));
        $this->assertSame(
            [
                ServiceWithNonResolvableUnionTypes::class => 1,
                ServiceWithNonExistingDependency::class => 1,
                \NonExisting::class => 1,
                ServiceWithPrivateConstructor::class => 1,
            ],
            $storage->getBuildStack()
        );
    }
}
