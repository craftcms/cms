<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function runAddColorsToAssetsMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_30_000000_add_colors_to_assets.php';
    $migration->up();
}

it('adds the colors column to installs that predate it', function () {
    Schema::table(Table::ASSETS, fn (Blueprint $table) => $table->dropColumn('colors'));

    runAddColorsToAssetsMigration();

    expect(Schema::hasColumn(Table::ASSETS, 'colors'))->toBeTrue();
});

it('replaces the dominantColor column on installs that added it', function () {
    Schema::table(Table::ASSETS, function (Blueprint $table) {
        $table->dropColumn('colors');
        $table->string('dominantColor', 7)->nullable();
    });

    runAddColorsToAssetsMigration();

    expect(Schema::hasColumn(Table::ASSETS, 'colors'))->toBeTrue()
        ->and(Schema::hasColumn(Table::ASSETS, 'dominantColor'))->toBeFalse();
});

it('leaves up-to-date installs alone', function () {
    runAddColorsToAssetsMigration();

    expect(Schema::hasColumn(Table::ASSETS, 'colors'))->toBeTrue()
        ->and(Schema::hasColumn(Table::ASSETS, 'dominantColor'))->toBeFalse();
});
