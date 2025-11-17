<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;

test('unique', function () {
    $site1 = Site::firstOrFail();
    $site2 = Site::factory()->create();

    Sites::refreshSites();

    $entry = Entry::factory()->create();
    $entry->element->siteSettings()->create([
        'siteId' => $site2->id,
    ]);
    $entry->section->siteSettings()->create([
        'siteId' => $site2->id,
    ]);

    expect(entryQuery()->site('*')->count())->toBe(2);
    expect(entryQuery()->site('*')->unique()->count())->toBe(1);
    expect(entryQuery()->site('*')->unique()->first()->siteId)->toBe($site1->id);

    Sites::setCurrentSite($site2->handle);

    expect(entryQuery()->site('*')->unique()->first()->siteId)->toBe($site2->id);

    expect(entryQuery()->site('*')->preferSites([$site2->id, $site1->id])->unique()->first()->siteId)->toBe($site2->id);
    expect(entryQuery()->site('*')->preferSites([$site2->handle, $site1->handle])->unique()->first()->siteId)->toBe($site2->id);
});
