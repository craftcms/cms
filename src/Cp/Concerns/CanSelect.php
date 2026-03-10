<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

trait CanSelect
{
    abstract public function label(): string;

    /** @return list<array{value: string, label: string}> */
    public static function asOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
