<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\EntryType\EntryTypes
 */
final class EntryTypes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\EntryType\EntryTypes::class;
    }
}
