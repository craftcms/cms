<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Noon in the tests’ America/Los_Angeles system time zone
    Date::setTestNow('2026-08-25 12:00:00');
});

it('binds dates in UTC', function () {
    $site = Site::factory()->create();

    DB::table(Table::SITES)->where('id', $site->id)->update(['dateUpdated' => now()]);

    expect(DB::table(Table::SITES)->where('id', $site->id)->value('dateUpdated'))->toBe('2026-08-25 19:00:00')
        ->and(DB::table(Table::SITES)->where('id', $site->id)->where('dateUpdated', now())->exists())->toBeTrue();
});

it('stores model timestamps in UTC and reads them back as the same moment', function () {
    $site = Site::factory()->create();

    expect(DB::table(Table::SITES)->where('id', $site->id)->value('dateCreated'))->toBe('2026-08-25 19:00:00')
        ->and(Site::findOrFail($site->id)->dateCreated->getTimestamp())->toBe(now()->getTimestamp());
});

it('stores soft-delete dates in UTC', function () {
    $entry = EntryModel::factory()->createElement();

    Element::findOrFail($entry->id)->delete();

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBe('2026-08-25 19:00:00');
});
