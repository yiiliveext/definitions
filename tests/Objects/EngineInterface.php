<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

/**
 * EngineInterface defines car engine interface
 */
interface EngineInterface
{
    public function getName(): string;

    public function setNumber(int $value): void;

    public function getNumber(): int;
}
