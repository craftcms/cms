<?php

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;

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
        'dateDeleted' => now()->subSeconds(app(GeneralConfig::class)->softDeleteDuration + 1),
    ]);

    expect(DB::table(Table::ENTRYTYPES)->count())->toBe(3);

    app(HardDelete::class, [
        'tables' => Table::ENTRYTYPES,
    ])();

    expect(DB::table(Table::ENTRYTYPES)->count())->toBe(2);
});
