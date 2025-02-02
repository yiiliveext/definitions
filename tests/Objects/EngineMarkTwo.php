<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class EngineMarkTwo implements EngineInterface
{
    public const NAME = 'Mark Two';

    private int $number;

    public function getName(): string
    {
        return self::NAME;
    }

    public function setNumber(int $value): void
    {
        $this->number = $value;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
