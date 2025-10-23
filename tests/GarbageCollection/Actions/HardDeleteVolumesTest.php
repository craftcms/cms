<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteVolumes;

it('deletes trashed volumes and their folders', function () {
    createVolume(['dateDeleted' => null]);
    createVolume(['dateDeleted' => now()]);
    createVolume(['dateDeleted' => now()->subSeconds(Cms::config()->softDeleteDuration + 1)]);

    expect(DB::table(Table::VOLUMES)->count())->toBe(3);
    expect(DB::table(Table::VOLUMEFOLDERS)->count())->toBe(3);

    app(HardDeleteVolumes::class)();

    expect(DB::table(Table::VOLUMES)->count())->toBe(2);
    expect(DB::table(Table::VOLUMEFOLDERS)->count())->toBe(2);
});

function createVolume(array $overrides = [])
{
    $volumeId = DB::table(Table::VOLUMES)->insertGetId(array_merge([
        'name' => fake()->words(3, true),
        'handle' => fake()->slug(1),
        'fs' => 'local',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ], $overrides));

    DB::table(Table::VOLUMEFOLDERS)->insert([
        'volumeId' => $volumeId,
        'name' => fake()->words(3, true),
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);
}
