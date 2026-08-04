<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use BackedEnum;

/** @phpstan-require-implements BackedEnum */
trait CanSelect
{
    abstract public function label(): string;

    /** @return list<array{value: string|int, label: string}> */
    public static function asOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
