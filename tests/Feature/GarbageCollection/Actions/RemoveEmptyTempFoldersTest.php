<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\RemoveEmptyTempFolders;
use Illuminate\Support\Facades\DB;

it('removes empty temp folders', function () {
    $parentId = DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'parent',
        'path' => 'parent',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    DB::table(Table::VOLUMEFOLDERS)->insert([
        'name' => 'foo',
        'parentId' => $parentId,
        'path' => 'foo',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(2);

    app(RemoveEmptyTempFolders::class)();

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(1);
});
