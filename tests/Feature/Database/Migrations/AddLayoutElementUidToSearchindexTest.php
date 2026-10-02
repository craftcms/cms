<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function runAddLayoutElementUidToSearchindexMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_02_000000_add_layout_element_uid_to_searchindex.php';
    $migration->up();
}

it('adds the layoutElementUid column to the search index primary key', function () {
    Schema::table(Table::SEARCHINDEX, fn (Blueprint $table) => $table->dropPrimary());
    Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
        $table->dropColumn('layoutElementUid');
        $table->primary(['elementId', 'attribute', 'fieldId', 'siteId']);
    });
    DB::table(Table::SEARCHINDEX)->insert([
        'elementId' => 1,
        'attribute' => 'field',
        'fieldId' => 1,
        'siteId' => 1,
        'keywords' => ' apple ',
    ]);

    runAddLayoutElementUidToSearchindexMigration();

    $primaryKey = collect(Schema::getIndexes(Table::SEARCHINDEX))->firstWhere('primary', true);

    expect($primaryKey['columns'])->toEqualCanonicalizing(['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId'])
        ->and(DB::table(Table::SEARCHINDEX)->where('elementId', 1)->value('layoutElementUid'))->toBe('0');
});

it('leaves up-to-date installs alone', function () {
    runAddLayoutElementUidToSearchindexMigration();

    expect(Schema::hasColumn(Table::SEARCHINDEX, 'layoutElementUid'))->toBeTrue();
});
