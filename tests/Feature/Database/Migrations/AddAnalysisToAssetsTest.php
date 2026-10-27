<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function runAddAnalysisToAssetsMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_26_000002_add_analysis_to_assets.php';
    $migration->up();
}

/**
 * Puts the assets table back how earlier builds left it, with colors, keywords, and visible text in their own columns.
 */
function addPreAnalysisAssetColumns(): void
{
    Schema::table(Table::ASSETS, function (Blueprint $table) {
        $table->dropColumn('analysis');
    });
    Schema::table(Table::ASSETS, function (Blueprint $table) {
        $table->json('colors')->nullable();
        $table->json('keywords')->nullable();
        $table->text('visibleText')->nullable();
    });
}

it('moves images’ colors, keywords, and visible text into analysis', function () {
    $tagged = Asset::factory()->create(['kind' => 'image']);
    $sampled = Asset::factory()->create(['kind' => 'image']);
    $document = Asset::factory()->create(['kind' => 'pdf']);
    addPreAnalysisAssetColumns();
    DB::table(Table::ASSETS)->where('id', $tagged->id)->update([
        'colors' => json_encode(['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28']]),
        'keywords' => json_encode(['harbor', 'dusk']),
        'visibleText' => 'Pier 9',
    ]);
    DB::table(Table::ASSETS)->where('id', $sampled->id)->update([
        'colors' => json_encode(['dominant' => null, 'grid' => []]),
    ]);

    runAddAnalysisToAssetsMigration();

    $analysis = fn (int $id): ?array => json_decode((string) DB::table(Table::ASSETS)->where('id', $id)->value('analysis'), true);

    expect($analysis($tagged->id))->toEqual([
        'colors' => ['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28'], 'luminance' => []],
        'metadata' => null,
        'type' => null,
        'keywords' => ['harbor', 'dusk'],
        'text' => 'Pier 9',
    ])
        ->and($analysis($sampled->id))->toEqual([
            'colors' => ['dominant' => null, 'grid' => [], 'palette' => [], 'luminance' => []],
            'metadata' => null,
            'type' => null,
            'keywords' => null,
            'text' => null,
        ])
        ->and($analysis($document->id))->toBeNull()
        ->and(Schema::hasColumns(Table::ASSETS, ['colors']))->toBeFalse()
        ->and(Schema::hasColumns(Table::ASSETS, ['keywords']))->toBeFalse()
        ->and(Schema::hasColumns(Table::ASSETS, ['visibleText']))->toBeFalse();
});

it('leaves up-to-date installs alone', function () {
    $asset = Asset::factory()->create(['kind' => 'image']);
    DB::table(Table::ASSETS)->where('id', $asset->id)->update(['analysis' => json_encode(['keywords' => ['harbor']])]);

    runAddAnalysisToAssetsMigration();

    expect(json_decode((string) DB::table(Table::ASSETS)->where('id', $asset->id)->value('analysis'), true))->toBe(['keywords' => ['harbor']]);
});
