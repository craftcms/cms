<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('updates storage for asset processor profiles', function () {
    if (! Schema::hasColumn(Table::VOLUMES, 'transformFs')) {
        Schema::table(Table::VOLUMES, function (Blueprint $table): void {
            $table->string('transformFs')->nullable();
            $table->string('transformSubpath')->nullable();
        });
    }

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_08_14_000000_asset_processors.php';

    $migration->up();

    expect(Schema::hasColumn(Table::VOLUMES, 'assetProcessor'))->toBeTrue()
        ->and(Schema::hasColumn(Table::VOLUMES, 'transformFs'))->toBeFalse()
        ->and(Schema::hasColumn(Table::VOLUMES, 'transformSubpath'))->toBeFalse()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'operations'))->toBeTrue()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'driver'))->toBeFalse();
});
