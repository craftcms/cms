<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function runCreateImageHashesTableMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_26_000004_create_imagehashes_table.php';
    $migration->up();
}

it('indexes the hashes of images sampled before the index existed', function () {
    $hashed = Asset::factory()->create(['kind' => 'image']);
    $unhashed = Asset::factory()->create(['kind' => 'image']);
    Schema::drop(Table::IMAGEHASHES);
    DB::table(Table::ASSETS)->where('id', $hashed->id)->update(['analysis' => json_encode(['hash' => 'fcfbf8e7ecdad4a3'])]);
    DB::table(Table::ASSETS)->where('id', $unhashed->id)->update(['analysis' => json_encode(['keywords' => ['harbor']])]);

    runCreateImageHashesTableMigration();
    runCreateImageHashesTableMigration();

    expect(DB::table(Table::IMAGEHASHES)->where('assetId', $hashed->id)->orderBy('band')->pluck('value')->all())->toBe(['fcfb', 'f8e7', 'ecda', 'd4a3'])
        ->and(DB::table(Table::IMAGEHASHES)->where('assetId', $unhashed->id)->exists())->toBeFalse();
});
