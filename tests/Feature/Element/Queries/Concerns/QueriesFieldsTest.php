<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use Illuminate\Support\Facades\DB;

test('id', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->id('not '.$element1->id.','.$element2->id)->get())->toHaveCount(1);
    expect(entryQuery()->id($element1->id)->get())->toHaveCount(1);
    expect(entryQuery()->id([$element1->id, $element2->id])->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(',', [$element1->id, $element2->id]))->get())->toHaveCount(2);
    expect(entryQuery()->id(implode(', ', [$element1->id, $element2->id]))->get())->toHaveCount(2);

    expect(entryQuery()->id("> {$element1->id}")->get())->toHaveCount(2);
    expect(entryQuery()->id(">= {$element1->id}")->get())->toHaveCount(3);
    expect(entryQuery()->id("and >={$element1->id}, >{$element2->id}")->get())->toHaveCount(1);
});

test('uid', function () {
    [$element1, $element2] = EntryModel::factory(3)->create();

    expect(entryQuery()->uid($element1->element->uid)->get())->toHaveCount(1);
    expect(entryQuery()->uid('not '.$element1->element->uid)->get())->toHaveCount(2);
    expect(entryQuery()->uid([$element1->element->uid, $element2->element->uid])->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(',', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
    expect(entryQuery()->uid(implode(', ', [$element1->element->uid, $element2->element->uid]))->get())->toHaveCount(2);
});

test('siteSettingsId', function () {
    [$element1, $element2] = EntryModel::factory(2)->create();

    expect(entryQuery()->siteSettingsId($element1->element->siteSettings->first()->id)->get())->toHaveCount(1);
    expect(entryQuery()->siteSettingsId($element1->element->siteSettings->first()->id)->first()->id)->toBe($element1->id);
    expect(entryQuery()->siteSettingsId([$element1->element->siteSettings->first()->id, $element2->element->siteSettings->first()->id])->get())->toHaveCount(2);
});

test('trashed', function () {
    EntryModel::factory()->trashed()->create();
    EntryModel::factory()->create();

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->trashed()->count())->toBe(1);
    expect(entryQuery()->trashed(null)->count())->toBe(2);
});

test('dateCreated & dateUpdated', function (string $column, mixed $param, int $expectedCount) {
    // Yesterday
    EntryModel::factory()->create()->element->update([
        $column => today()->subDay(),
    ]);

    // Today
    EntryModel::factory()->create()->element->update([
        $column => today(),
    ]);

    // Tomorrow
    EntryModel::factory()->create()->element->update([
        $column => today()->addDay(),
    ]);

    expect(entryQuery()->$column($param)->count())->toBe($expectedCount);
})->with([
    'dateCreated',
    'dateUpdated',
])->with([
    ['<= yesterday', 1],
    [['< today', '> today'], 2],
    [['and', '> yesterday', '> today'], 1],
]);

test('title, slug & uri', function (string $attribute) {
    EntryModel::factory()->create()->element->siteSettings->first()->update([
        $attribute => 'String 1',
    ]);

    EntryModel::factory()->create()->element->siteSettings->first()->update([
        $attribute => 'String 2',
    ]);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->$attribute('String 1')->count())->toBe(1);
    expect(entryQuery()->$attribute('String 2')->count())->toBe(1);
    expect(entryQuery()->$attribute('String*')->count())->toBe(2);
})->with([
    'title',
    'slug',
    'uri',
]);

test('inBulkOp', function () {
    $entry = EntryModel::factory()->create();

    EntryModel::factory()->create();

    DB::table(Table::ELEMENTS_BULKOPS)
        ->insert([
            'elementId' => $entry->id,
            'key' => 'foo',
            'timestamp' => now(),
        ]);

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->inBulkOp('foo')->count())->toBe(1);
    expect(entryQuery()->inBulkOp('non-existing')->count())->toBe(0);
});
