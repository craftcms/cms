<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Translation\I18N
 */
final class I18N extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Translation\I18N::class;
    }
}
