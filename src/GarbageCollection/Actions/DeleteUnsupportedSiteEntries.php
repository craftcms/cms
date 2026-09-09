<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\DB;

/**
 * Deletes entries for sites that aren’t enabled by their section.
 *
 * This can happen if you entrify a category group, disable one of the sites in the newly-created section’s
 * settings, then deploy those changes to another environment, apply project config changes, and re-run the
 * entrify command. (https://github.com/craftcms/cms/issues/13383)
 */
class DeleteUnsupportedSiteEntries extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        private readonly Sites $sites,
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        $this->components->task(
            'deleting entries in unsupported sites',
            function () {
                $siteIds = $this->sites->getAllSiteIds(true);

                foreach (Sections::getAllSections() as $section) {
                    $unsupportedSiteIds = $siteIds->diff(array_keys($section->getSiteSettings()));

                    if ($unsupportedSiteIds->isEmpty()) {
                        continue;
                    }

                    DB::table(Table::ELEMENTS_SITES)
                        ->whereIn('siteId', $unsupportedSiteIds)
                        ->whereIn('elementId', DB::table(Table::ENTRIES)->select('id')->where('sectionId', $section->id))
                        ->delete();
                }
            },
        );
    }
}
