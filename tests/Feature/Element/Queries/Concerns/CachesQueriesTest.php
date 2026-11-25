<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\ElementCaches;
use Illuminate\Support\Facades\DB;

it('caches queries', function () {
    // Live entry queries compare post dates to the current minute, which changes their cache key
    $this->freezeTime();

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

it('caches queries by the expressions passed to them', function () {
    $this->freezeTime();

    Entry::factory()->create();

    expect(entryQuery()->cache()->pluck(DB::raw('1 + 1'))->all())->toEqual([2])
        ->and(entryQuery()->cache()->pluck(DB::raw('2 + 2'))->all())->toEqual([4])
        ->and(entryQuery()->cache()->sum(DB::raw('1')))->toEqual(1)
        ->and(entryQuery()->cache()->sum(DB::raw('5')))->toEqual(5);
});
