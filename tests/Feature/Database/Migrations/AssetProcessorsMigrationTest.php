<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('migrates volume transform destinations to asset processors', function () {
    if (! Schema::hasColumn(Table::VOLUMES, 'transformFs')) {
        Schema::table(Table::VOLUMES, function (Blueprint $table): void {
            $table->string('transformFs')->nullable();
            $table->string('transformSubpath')->nullable();
        });
    }

    $firstVolume = Volume::factory()->create([
        'name' => 'First Volume',
        'handle' => 'firstVolume',
        'transformFs' => 'disk:legacy-transforms',
        'transformSubpath' => 'renditions',
    ]);
    $secondVolume = Volume::factory()->create([
        'name' => 'Second Volume',
        'handle' => 'secondVolume',
        'transformFs' => 'disk:legacy-transforms',
        'transformSubpath' => 'renditions',
    ]);
    $sourceVolume = Volume::factory()->create([
        'name' => 'Source Volume',
        'handle' => 'sourceVolume',
        'transformSubpath' => 'source-renditions',
    ]);
    $defaultVolume = Volume::factory()->create([
        'name' => 'Default Volume',
        'handle' => 'defaultVolume',
    ]);

    $projectConfig = app(ProjectConfig::class);
    $projectConfig->muteEvents = true;

    foreach ([$firstVolume, $secondVolume, $sourceVolume, $defaultVolume] as $volume) {
        $projectConfig->set(ProjectConfig::PATH_VOLUMES.'.'.$volume->uid, [
            'name' => $volume->name,
            'handle' => $volume->handle,
            'fs' => $volume->fs,
            'transformFs' => $volume->transformFs,
            'transformSubpath' => $volume->transformSubpath,
        ]);
    }

    $projectConfig->muteEvents = false;

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_08_14_000000_asset_processors.php';

    $migration->up();

    $projectConfig->reset();
    app(AssetProcessors::class)->reset();
    app()->forgetInstance(Volumes::class);
    $volumes = app(Volumes::class);
    $firstProcessorHandle = $volumes->getVolumeByUid($firstVolume->uid)->getAssetProcessorHandle(false);
    $secondProcessorHandle = $volumes->getVolumeByUid($secondVolume->uid)->getAssetProcessorHandle(false);
    $sourceProcessorHandle = $volumes->getVolumeByUid($sourceVolume->uid)->getAssetProcessorHandle(false);
    $processors = app(AssetProcessors::class);

    expect(Schema::hasColumn(Table::VOLUMES, 'assetProcessor'))->toBeTrue()
        ->and(Schema::hasColumn(Table::VOLUMES, 'transformFs'))->toBeFalse()
        ->and(Schema::hasColumn(Table::VOLUMES, 'transformSubpath'))->toBeFalse()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'operations'))->toBeTrue()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'driver'))->toBeFalse()
        ->and($firstProcessorHandle)->not->toBeNull()
        ->and($secondProcessorHandle)->toBe($firstProcessorHandle)
        ->and($sourceProcessorHandle)->not->toBeNull()
        ->and($sourceProcessorHandle)->not->toBe($firstProcessorHandle)
        ->and($volumes->getVolumeByUid($defaultVolume->uid)->getAssetProcessorHandle(false))->toBeNull()
        ->and($processors->resolve($firstProcessorHandle)->settings)->toBe([
            'filesystem' => 'disk:legacy-transforms',
            'subpath' => 'renditions',
        ])
        ->and($processors->resolve($sourceProcessorHandle)->settings)->toBe([
            'filesystem' => null,
            'subpath' => 'source-renditions',
        ])
        ->and($projectConfig->get(ProjectConfig::PATH_VOLUMES.'.'.$firstVolume->uid.'.assetProcessor'))->toBe($firstProcessorHandle)
        ->and($projectConfig->get(ProjectConfig::PATH_VOLUMES.'.'.$sourceVolume->uid.'.assetProcessor'))->toBe($sourceProcessorHandle);
})->skip(
    fn () => DB::isMysql(),
    'MySQL implicitly commits the schema changes exercised by this test.',
);
