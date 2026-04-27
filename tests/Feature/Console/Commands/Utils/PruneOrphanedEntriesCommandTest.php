<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

it('prunes orphaned nested entries for each site', function () {
    $secondSite = Site::factory()->create();
    Sites::refreshSites();

    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();

    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondSite->id,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);

    $ownerEntry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create();

    $orphanedEntry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create(['primaryOwnerId' => $ownerEntry->id]);

    $orphanedEntry->element->siteSettings->first()->update(['siteId' => $secondSite->id]);

    $supportedOwnerEntry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create();

    $supportedOwnerEntry->element->siteSettings->first()->update(['siteId' => $secondSite->id]);

    $supportedEntry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create(['primaryOwnerId' => $supportedOwnerEntry->id]);

    $supportedEntry->element->siteSettings->first()->update(['siteId' => $secondSite->id]);

    $this->artisan('utils/prune-orphaned-entries')
        ->expectsOutputToContain(sprintf('Site "%s"', Site::first()->name))
        ->expectsOutputToContain(sprintf('Site "%s"', $secondSite->name))
        ->expectsOutputToContain("Deleting entry {$orphanedEntry->id} in {$secondSite->name}")
        ->expectsOutputToContain('Finished pruning orphaned entries. Deleted 1 entry.')
        ->assertSuccessful();

    expect(DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $orphanedEntry->id)
        ->doesntExist())->toBeTrue();

    expect(DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $supportedEntry->id)
        ->exists())->toBeTrue();
});
