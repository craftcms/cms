<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Structure\Structures
 */
final class Structures extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Structure\Structures::class;
    }
}
