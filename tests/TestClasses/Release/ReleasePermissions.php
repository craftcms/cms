<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Release;

use CraftCms\Cms\Section\Models\Section;

class ReleasePermissions
{
    /** @return list<string> */
    public static function forSection(Section $section, bool $manageReleases = false): array
    {
        return array_values(array_filter([
            $manageReleases ? 'manageReleases' : null,
            "viewEntries:$section->uid",
            "saveEntries:$section->uid",
            "viewPeerEntries:$section->uid",
            "savePeerEntries:$section->uid",
            "viewPeerEntryDrafts:$section->uid",
            "savePeerEntryDrafts:$section->uid",
        ]));
    }
}
