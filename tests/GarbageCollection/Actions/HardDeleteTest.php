<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use Illuminate\Support\Facades\DB;

it('hard deletes soft deleted elements', function () {
    // Not soft deleted
    DB::table(Table::ENTRYTYPES)->insert([
        'name' => 'Test',
        'handle' => 'test',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    // Soft deleted but within duration
    DB::table(Table::ENTRYTYPES)->insert([
        'name' => 'Test',
        'handle' => 'test',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'dateDeleted' => now(),
    ]);

    // Soft deleted before threshold
    DB::table(Table::ENTRYTYPES)->insert([
        'name' => 'Test',
        'handle' => 'test',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1),
    ]);

    expect(DB::table(Table::ENTRYTYPES)->count())->toBe(3);

    resolve(HardDelete::class, [
        'tables' => Table::ENTRYTYPES,
    ])();

    expect(DB::table(Table::ENTRYTYPES)->count())->toBe(2);
});
