<?php

use CraftCms\Cms\Entry\Models\Entry;

it('caches queries', function () {
    Entry::factory()->create();

    expect(entryQuery()->cache()->count())->toBe(1);
    expect(entryQuery()->cache()->all()->count())->toBe(1);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(1);

    Entry::factory()->create();

    // Cache is not cleared
    expect(entryQuery()->cache()->count())->toBe(1);
    expect(entryQuery()->cache()->all()->count())->toBe(1);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(1);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->all()->count())->toBe(2);

    Craft::$app->getElements()->invalidateCachesForElementType(\craft\elements\Entry::class);

    expect(entryQuery()->cache()->count())->toBe(2);
    expect(entryQuery()->cache()->all()->count())->toBe(2);
    expect(entryQuery()->cache()->pluck('id')->count())->toBe(2);
});
