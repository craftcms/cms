<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('drops alpha transformer profiles and everything keyed by their UIDs', function () {
    $volume = VolumeModel::factory()->create();
    $asset = Asset::factory()->createElement(['volumeId' => $volume->id]);
    $profileUid = '0f7dbd20-f39e-57db-8b25-dcadccdd1733';

    Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->string('assetTransformer')->nullable());
    Schema::table(Table::IMAGETRANSFORMINDEX, fn (Blueprint $table) => $table->renameColumn('outputKey', 'transformer'));
    DB::table(Table::VOLUMES)->where('id', $volume->id)->update(['assetTransformer' => 'imgix']);
    $transformId = DB::table(Table::IMAGETRANSFORMS)->insertGetId([
        'name' => 'Hero',
        'handle' => 'hero',
        'mode' => 'crop',
        'position' => 'center-center',
        'parameters' => Json::encode([$profileUid => ['blur' => 5]]),
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => $transformUid = (string) str()->uuid(),
    ]);
    $indexRow = fn (?string $transformer): int => DB::table(Table::IMAGETRANSFORMINDEX)->insertGetId([
        'assetId' => $asset->id,
        'transformer' => $transformer,
        'transformString' => '_100x_auto',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => (string) str()->uuid(),
    ]);
    $profileRow = $indexRow($profileUid);
    $craft5Row = $indexRow('craft\\imagetransforms\\ImageTransformer');
    $nullRow = $indexRow(null);

    $projectConfig = app(ProjectConfig::class);
    $projectConfig->muteEvents = true;
    $projectConfig->set('assetTransformers', [$profileUid => ['name' => 'Craft', 'handle' => 'craft', 'driver' => 'craft']]);
    $projectConfig->set(ProjectConfig::PATH_VOLUMES.".$volume->uid", ['handle' => 'images', 'assetTransformer' => 'imgix']);
    $projectConfig->set(ProjectConfig::PATH_IMAGE_TRANSFORMS.".$transformUid", [
        'name' => 'Hero',
        'handle' => 'hero',
        'parameters' => [$profileUid => ['blur' => 5]],
    ]);
    $projectConfig->muteEvents = false;

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_09_000000_disk_owned_asset_transformers.php';
    $migration->up();

    expect($projectConfig->get('assetTransformers'))->toBeNull()
        ->and($projectConfig->get(ProjectConfig::PATH_VOLUMES.".$volume->uid.assetTransformer"))->toBeNull()
        ->and($projectConfig->get(ProjectConfig::PATH_VOLUMES.".$volume->uid.handle"))->toBe('images')
        ->and($projectConfig->get(ProjectConfig::PATH_IMAGE_TRANSFORMS.".$transformUid.parameters"))->toBeNull()
        ->and($projectConfig->get(ProjectConfig::PATH_IMAGE_TRANSFORMS.".$transformUid.handle"))->toBe('hero')
        ->and(DB::table(Table::IMAGETRANSFORMS)->where('id', $transformId)->value('parameters'))->toBeNull()
        ->and(Schema::hasColumn(Table::VOLUMES, 'assetTransformer'))->toBeFalse()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMINDEX, 'transformer'))->toBeFalse()
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->pluck('outputKey', 'id')->all())->toEqualCanonicalizing([
            $craft5Row => 'craft\\imagetransforms\\ImageTransformer',
            $nullRow => null,
        ])
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $profileRow)->exists())->toBeFalse();
});
