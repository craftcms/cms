<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function runAddPropagationMethodToVolumesMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_22_000000_add_propagation_method_to_volumes.php';
    $migration->up();
}

function removeVolumePropagationMethods(VolumeModel $volume): void
{
    Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->dropColumn('propagationMethod'));

    $projectConfig = app(ProjectConfig::class);
    $projectConfig->muteEvents = true;
    $projectConfig->set(ProjectConfig::PATH_VOLUMES.".$volume->uid", [
        'name' => $volume->name,
        'handle' => $volume->handle,
        'fs' => $volume->fs,
    ]);
    $projectConfig->muteEvents = false;
}

it('sets existing volumes to custom propagation on single-site installs', function () {
    $volume = VolumeModel::factory()->create();
    removeVolumePropagationMethods($volume);

    runAddPropagationMethodToVolumesMigration();

    expect(DB::table(Table::VOLUMES)->where('id', $volume->id)->value('propagationMethod'))->toBe('custom')
        ->and(app(ProjectConfig::class)->get(ProjectConfig::PATH_VOLUMES.".$volume->uid.propagationMethod"))->toBe('custom');
});

it('keeps existing volumes saving assets to all sites on multi-site installs', function () {
    Site::factory()->create();
    $volume = VolumeModel::factory()->create();
    removeVolumePropagationMethods($volume);

    runAddPropagationMethodToVolumesMigration();

    expect(DB::table(Table::VOLUMES)->where('id', $volume->id)->value('propagationMethod'))->toBe('all')
        ->and(app(ProjectConfig::class)->get(ProjectConfig::PATH_VOLUMES.".$volume->uid.propagationMethod"))->toBe('all');
});

it('defaults new volumes to custom propagation', function () {
    Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->dropColumn('propagationMethod'));

    runAddPropagationMethodToVolumesMigration();

    $volume = VolumeModel::factory()->create();

    expect(DB::table(Table::VOLUMES)->where('id', $volume->id)->value('propagationMethod'))->toBe('custom');
});
