<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Contracts;

interface SelectableEnumInterface
{
    public function label(): string;

    /** @return list<array{value: int|string, label: string}> */
    public static function asOptions(): array;
}
