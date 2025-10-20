<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\RemoveEmptyTempFolders;

it('removes empty temp folders', function () {
    DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'foo',
        'parentId' => 1,
        'path' => 'foo',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(1);

    app(RemoveEmptyTempFolders::class)();

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(0);
});
