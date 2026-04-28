<?php

use CraftCms\Cms\Entry\Models\Entry as EntryModel;

test('postDate & expiryDate', function (string $column, mixed $param, int $expectedCount) {
    // Yesterday
    EntryModel::factory()->create([
        $column => today()->subDay(),
    ]);

    // Today
    EntryModel::factory()->create([
        $column => today(),
    ]);

    // Tomorrow
    EntryModel::factory()->create([
        $column => today()->addDay(),
    ]);

    // status(null) otherwise it filters out the entry from tomorrow
    expect(entryQuery()->$column($param)->status(null)->count())->toBe($expectedCount);
})->with([
    'postDate',
    'expiryDate',
])->with([
    ['<= yesterday', 1],
    [['< today', '> today'], 2],
    [['and', '> yesterday', '> today'], 1],
]);

test('before & after', function () {
    // Yesterday
    EntryModel::factory()->create([
        'postDate' => today()->subDay(),
    ]);

    // Today
    EntryModel::factory()->create([
        'postDate' => today(),
    ]);

    // Tomorrow
    EntryModel::factory()->create([
        'postDate' => today()->addDay(),
    ]);

    expect(entryQuery()->before('today')->status(null)->count())->toBe(1);
    expect(entryQuery()->before('tomorrow')->status(null)->count())->toBe(2);

    expect(entryQuery()->after('today')->status(null)->count())->toBe(2);
    expect(entryQuery()->after('tomorrow')->status(null)->count())->toBe(1);
});
