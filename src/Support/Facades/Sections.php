<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Events\SiteDeleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getAllSectionIds()
 * @method static Collection getEditableSectionIds()
 * @method static Collection getAllSections()
 * @method static Collection getEditableSections()
 * @method static Collection getSectionsByType(SectionType $type)
 * @method static int getTotalSections()
 * @method static int getTotalEditableSections()
 * @method static Section|null getSectionById(int $sectionId)
 * @method static Section|null getSectionByUid(string $uid)
 * @method static Section|null getSectionByHandle(string $sectionHandle)
 * @method static SectionSiteSettings[] getSectionSiteSettings(int $sectionId)
 * @method static bool saveSection(Section $section, bool $runValidation = true)
 * @method static void handleChangedSection(ConfigEvent $event)
 * @method static void refreshSections()
 * @method static bool deleteSectionById(int $sectionId)
 * @method static bool deleteSection(Section $section)
 * @method static void handleDeletedSection(ConfigEvent $event)
 * @method static void pruneDeletedSite(SiteDeleted $event)
 * @method static array getSectionTableData(int $page, int $limit, string|null $searchTerm = null, string $orderBy = 'name', int $sortDir = 4)
 *
 * @see \CraftCms\Cms\Section\Sections
 */
final class Sections extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Section\Sections::class;
    }
}
