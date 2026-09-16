<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\GarbageCollection\Actions\DeleteUnsupportedSiteEntries;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\DB;

it('deletes only unsupported site rows for each section, including disabled sites', function () {
    $sites = [Site::first(), Site::factory()->create(), Site::factory()->create(['enabled' => 'false'])];
    $remainingIds = DB::table(Table::ELEMENTS_SITES)->pluck('id')->all();

    foreach ([[0], [1], [0, 2], [], [0, 1, 2]] as $supported) {
        $section = Section::factory()->withSites(...$sites)->create();
        $entry = Entry::factory()->forSection($section)->create();
        DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->delete();

        foreach ($sites as $index => $site) {
            $id = DB::table(Table::ELEMENTS_SITES)->insertGetId([
                'elementId' => $entry->id,
                'siteId' => $site->id,
                'uid' => (string) str()->uuid(),
                'dateCreated' => now(),
                'dateUpdated' => now(),
            ]);

            if (in_array($index, $supported, true)) {
                $remainingIds[] = $id;
            } else {
                SectionSiteSettings::where('sectionId', $section->id)->where('siteId', $site->id)->delete();
            }
        }
    }

    Sections::refreshSections();
    app(DeleteUnsupportedSiteEntries::class)();

    expect(DB::table(Table::ELEMENTS_SITES)->orderBy('id')->pluck('id')->all())->toBe($remainingIds);
});
