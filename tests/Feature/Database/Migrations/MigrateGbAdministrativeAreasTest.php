<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

it('stores GB counties from Craft 5 by their ISO codes', function () {
    $ids = collect([
        ['GB', 'Surrey'],
        ['GB', 'Durham, County'],
        ['GB', 'SRY'],
        ['GB', 'Somewhere else'],
        ['US', 'Surrey'],
    ])->map(fn (array $address): int => AddressModel::factory()->create(['countryCode' => $address[0], 'administrativeArea' => $address[1]])->id);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_08_000000_migrate_gb_administrative_areas.php';
    $migration->up();

    expect($ids->map(fn (int $id): ?string => DB::table(Table::ADDRESSES)->where('id', $id)->value('administrativeArea'))->all())
        ->toBe(['SRY', 'DUR', 'SRY', 'Somewhere else', 'Surrey']);
});
