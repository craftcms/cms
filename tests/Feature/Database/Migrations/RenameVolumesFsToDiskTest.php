<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    // MySQL commits implicitly on DDL, so the volume created before the column was renamed outlives the test's transaction
    DB::table(Table::VOLUMES)->delete();
});

it('renames the volumes fs column and project config key to disk', function () {
    $volume = VolumeModel::factory()->create(['disk' => 'legacy-disk']);
    Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->renameColumn('disk', 'fs'));

    $projectConfig = app(ProjectConfig::class);
    $projectConfig->muteEvents = true;
    $projectConfig->set(ProjectConfig::PATH_VOLUMES.".$volume->uid", [
        'name' => $volume->name,
        'handle' => $volume->handle,
        'fs' => '$LEGACY_DISK',
        'hasUrls' => true,
    ]);
    $projectConfig->muteEvents = false;

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_20_000000_rename_volumes_fs_to_disk.php';
    $migration->up();

    expect(Schema::hasColumn(Table::VOLUMES, 'fs'))->toBeFalse()
        ->and(DB::table(Table::VOLUMES)->where('id', $volume->id)->value('disk'))->toBe('legacy-disk')
        ->and($projectConfig->get(ProjectConfig::PATH_VOLUMES.".$volume->uid"))->toEqual([
            'name' => $volume->name,
            'handle' => $volume->handle,
            'hasUrls' => true,
            'disk' => '$LEGACY_DISK',
        ]);
});
