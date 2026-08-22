<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Schema;

it('adds storage for volume transformer references and named transform operations', function () {
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_08_14_000000_asset_transformers.php';

    $migration->up();

    expect(Schema::hasColumn(Table::VOLUMES, 'assetTransformer'))->toBeTrue()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'operations'))->toBeTrue()
        ->and(Schema::hasColumn(Table::IMAGETRANSFORMS, 'driver'))->toBeFalse();
});
