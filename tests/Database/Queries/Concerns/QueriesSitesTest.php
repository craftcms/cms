<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;

it('can query elements by site', function () {
    $site1 = Site::firstOrFail();
    $site2 = Site::factory()->create();

    Entry::factory()->create();

    $entry2 = Entry::factory()->create();
    $entry2->element->siteSettings->first()->update(['siteId' => $site2->id]);
    $entry2->section->siteSettings->first()->update(['siteId' => $site2->id]);

    Sites::refreshSites();

    expect(entryQuery()->count())->toBe(1); // Defaults to current site (1)
    expect(entryQuery()->siteId($site1->id)->count())->toBe(1);
    expect(entryQuery()->siteId($site2->id)->count())->toBe(1);
    expect(entryQuery()->siteId([$site1->id, $site2->id])->count())->toBe(2);
    expect(entryQuery()->siteId(implode(', ', [$site1->id, $site2->id]))->count())->toBe(2);

    expect(entryQuery()->site('*')->count())->toBe(2);
    expect(entryQuery()->site($site1->handle)->count())->toBe(1);
    expect(entryQuery()->site($site2->handle)->count())->toBe(1);
    expect(entryQuery()->site([$site1->handle, $site2->handle])->count())->toBe(2);
    expect(entryQuery()->site(implode(', ', [$site1->handle, $site2->handle]))->count())->toBe(2);

    expect(entryQuery()->site(['not', $site1->handle])->count())->toBe(1);
    expect(entryQuery()->site(['not', $site2->handle])->count())->toBe(1);
});
