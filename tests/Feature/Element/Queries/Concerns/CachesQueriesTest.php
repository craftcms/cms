<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\ElementCaches;

it('caches queries', function () {
    Entry::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);
    expect(entryQuery()->cache()->get()->count())->toBe(1);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(1);

    Entry::factory()->create();

    // Cache is not cleared
    expect(entryQuery()->cache()->count())->toBe(1);
    expect(entryQuery()->cache()->get()->count())->toBe(1);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(1);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->get()->count())->toBe(2);

    ElementCaches::invalidateForElementType(CraftCms\Cms\Entry\Elements\Entry::class);

    expect(entryQuery()->cache()->count())->toBe(2);
    expect(entryQuery()->cache()->get()->count())->toBe(2);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(2);
});
