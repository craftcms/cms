<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Section\Sections
 */
final class Sections extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Section\Sections::class;
    }
}
