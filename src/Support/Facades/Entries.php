<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Entry|null getEntryById(int $entryId, int|string|int[]|null $siteId = null, array $criteria = [])
 * @method static array getSingleEntriesByHandle(string[] $handles)
 * @method static void refreshSingleEntries()
 * @method static bool moveEntryToSection(Entry $entry, Section $section)
 *
 * @see \CraftCms\Cms\Entry\Entries
 */
final class Entries extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Entry\Entries::class;
    }
}
