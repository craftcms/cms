<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isActive()
 * @method static void setActive(bool $active)
 * @method static mixed withActive(bool $active, callable $callback)
 * @method static void registerName(string $inputName, bool $forceModified = false)
 * @method static string[] getNames()
 * @method static string[] getModifiedNames()
 * @method static void setInitialValue(string $inputName, mixed $value)
 * @method static array<string, mixed> getInitialValues()
 *
 * @see \CraftCms\Cms\View\DeltaRegistry
 */
class DeltaRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\DeltaRegistry::class;
    }
}
