<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function runCreateReleaseTablesMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_10_15_000000_create_release_tables.php';
    $migration->up();
}

it('renames an existing plannedAt column to targetDate, keeping its values', function () {
    Schema::table(Table::RELEASES, fn (Blueprint $table) => $table->renameColumn('targetDate', 'plannedAt'));
    DB::table(Table::RELEASES)->insert([
        'name' => 'Launch',
        'plannedAt' => '2026-10-16 06:30:00',
        'status' => 'draft',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    runCreateReleaseTablesMigration();

    expect(Schema::hasColumn(Table::RELEASES, 'plannedAt'))->toBeFalse()
        ->and(DB::table(Table::RELEASES)->where('name', 'Launch')->value('targetDate'))->toStartWith('2026-10-16 06:30:00');
});

it('leaves up-to-date installs alone', function () {
    runCreateReleaseTablesMigration();

    expect(Schema::hasColumn(Table::RELEASES, 'targetDate'))->toBeTrue()
        ->and(Schema::hasColumn(Table::RELEASES, 'plannedAt'))->toBeFalse();
});
