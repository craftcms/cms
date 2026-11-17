<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

it('generates image types from images’ analysis, and keeps them up to date', function () {
    $asset = Asset::factory()->create(['kind' => 'image']);
    dropAssetImageTypeColumn();
    DB::table(Table::ASSETS)->where('id', $asset->id)->update(['analysis' => json_encode(['type' => 'diagram'])]);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_26_000004_add_image_type_column_to_assets.php';
    $migration->up();
    $migration->up();

    $imageType = fn (): ?string => DB::table(Table::ASSETS)->where('id', $asset->id)->value('imageType');
    expect($imageType())->toBe('diagram');

    DB::table(Table::ASSETS)->where('id', $asset->id)->update(['analysis' => json_encode(['type' => 'logo'])]);
    expect($imageType())->toBe('logo');
});
