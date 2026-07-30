<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use CraftCms\Cms\Cp\Contracts\SelectableEnumInterface;

/** @phpstan-require-implements SelectableEnumInterface */
trait CanSelect
{
    abstract public function label(): string;

    public static function asOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
