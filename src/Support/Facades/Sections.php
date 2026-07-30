<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllSectionIds()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getEditableSectionIds()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getAllSections()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getEditableSections()
 * @method static \Illuminate\Support\Collection<array-key, mixed> getPublishableSections()
 * @method static array<array-key, mixed> getAvailableEntryMoveTargetSections(array<array-key, mixed> $entryTypeIds, int $siteId, string $currentSectionUid)
 * @method static \Illuminate\Support\Collection<array-key, mixed> getSectionsByType(\CraftCms\Cms\Section\Enums\SectionType $type)
 * @method static int getTotalSections()
 * @method static int getTotalEditableSections()
 * @method static \CraftCms\Cms\Section\Data\Section|null getSectionById(int $sectionId)
 * @method static \CraftCms\Cms\Section\Data\Section|null getSectionByUid(string $uid)
 * @method static \CraftCms\Cms\Section\Data\Section|null getSectionByHandle(string $sectionHandle)
 * @method static \CraftCms\Cms\Section\Data\SectionSiteSettings[] getSectionSiteSettings(int $sectionId)
 * @method static bool saveSection(\CraftCms\Cms\Section\Data\Section $section, bool $runValidation = true)
 * @method static void handleChangedSection(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void refreshSections()
 * @method static bool deleteSectionById(int $sectionId)
 * @method static bool deleteSection(\CraftCms\Cms\Section\Data\Section $section)
 * @method static void handleDeletedSection(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void pruneDeletedSite(\CraftCms\Cms\Site\Events\SiteDeleted $event)
 * @method static array<array-key, mixed> getSectionTableData(int $page, int $limit, string|null $searchTerm = null, string $orderBy = 'name', int $sortDir = 4)
 *
 * @see \CraftCms\Cms\Section\Sections
 */
class Sections extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Section\Sections::class;
    }
}
