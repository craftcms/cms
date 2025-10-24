<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection getEntryTypesBySectionId(int $sectionId)
 * @method static \Illuminate\Support\Collection getAllEntryTypes()
 * @method static \CraftCms\Cms\EntryType\Data\EntryType|null getEntryTypeById(int $entryTypeId, bool $withTrashed = false)
 * @method static \CraftCms\Cms\EntryType\Data\EntryType|null getEntryTypeByUid(string $uid)
 * @method static \CraftCms\Cms\EntryType\Data\EntryType|null getEntryTypeByHandle(string $entryTypeHandle)
 * @method static \CraftCms\Cms\EntryType\Data\EntryType|null getEntryType(\CraftCms\Cms\EntryType\Data\EntryType|int|string|array $entryType)
 * @method static bool saveEntryType(\CraftCms\Cms\EntryType\Data\EntryType $entryType)
 * @method static void handleChangedEntryType(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteEntryTypeById(int $entryTypeId)
 * @method static bool deleteEntryType(\CraftCms\Cms\EntryType\Data\EntryType $entryType)
 * @method static void handleDeletedEntryType(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void refreshEntryTypes()
 *
 * @see \CraftCms\Cms\EntryType\EntryTypes
 */
final class EntryTypes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\EntryType\EntryTypes::class;
    }
}
