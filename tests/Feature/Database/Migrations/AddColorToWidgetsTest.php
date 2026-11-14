<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\Updates;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('adds a color column that keeps existing widgets gray', function () {
    Schema::table(Table::WIDGETS, fn (Blueprint $table) => $table->dropColumn('color'));

    $id = DB::table(Table::WIDGETS)->insertGetId([
        'userId' => 1,
        'type' => Updates::class,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_13_000000_add_color_to_widgets.php';
    $migration->up();
    $migration->up();

    expect(DB::table(Table::WIDGETS)->where('id', $id)->value('color'))->toBe('gray');
});
