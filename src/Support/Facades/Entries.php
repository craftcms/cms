<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CraftCms\Cms\Entry\Elements\Entry|null getEntryById(int $entryId, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static array getSingleEntriesByHandle(string[] $handles)
 * @method static void refreshSingleEntries()
 * @method static bool moveEntryToSection(\CraftCms\Cms\Entry\Elements\Entry $entry, \CraftCms\Cms\Section\Data\Section $section)
 *
 * @see \CraftCms\Cms\Entry\Entries
 */
class Entries extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Entry\Entries::class;
    }
}
