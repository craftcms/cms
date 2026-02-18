<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getEntryTypesBySectionId(int $sectionId)
 * @method static Collection getAllEntryTypes()
 * @method static EntryType|null getEntryTypeById(int $entryTypeId, bool $withTrashed = false)
 * @method static EntryType|null getEntryTypeByUid(string $uid)
 * @method static EntryType|null getEntryTypeByHandle(string $entryTypeHandle)
 * @method static EntryType|null getEntryType(EntryType|int|string|array $entryType)
 * @method static bool saveEntryType(EntryType $entryType)
 * @method static void handleChangedEntryType(ConfigEvent $event)
 * @method static bool deleteEntryTypeById(int $entryTypeId)
 * @method static bool deleteEntryType(EntryType $entryType)
 * @method static void handleDeletedEntryType(ConfigEvent $event)
 * @method static void refreshEntryTypes()
 *
 * @see \CraftCms\Cms\Entry\EntryTypes
 */
final class EntryTypes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Entry\EntryTypes::class;
    }
}
