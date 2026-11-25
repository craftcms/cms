<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the tables an install migration would, with Laravel's default index and foreign key names.
 */
function createArchiveTestTables(): void
{
    Schema::create('archivetest_groups', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('archivetest_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('groupId')->nullable()->constrained('archivetest_groups')->nullOnDelete();
        $table->string('name')->unique();
        $table->index(['groupId', 'name']);
    });
}

afterEach(function () {
    foreach (['archivetest_items', 'archivetest_items_old', 'archivetest_items_old1', 'archivetest_groups', 'archivetest_groups_old', 'archivetest_groups_old1'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('archives tables so they can be created again', function () {
    $migration = new class extends Migration {};

    for ($i = 1; $i <= 3; $i++) {
        foreach (['archivetest_items', 'archivetest_groups'] as $table) {
            $migration->archiveTableIfExists($table);
        }

        createArchiveTestTables();
        $groupId = DB::table('archivetest_groups')->insertGetId(['name' => "Group $i"]);
        DB::table('archivetest_items')->insert(['groupId' => $groupId, 'name' => "Item $i"]);
    }

    expect(DB::table('archivetest_items_old')->value('name'))->toBe('Item 1')
        ->and(DB::table('archivetest_items_old1')->value('name'))->toBe('Item 2')
        ->and(DB::table('archivetest_groups_old1')->value('name'))->toBe('Group 2')
        ->and(DB::table('archivetest_items')->pluck('name')->all())->toBe(['Item 3'])
        ->and(DB::table('archivetest_groups')->value('id'))->toBe(DB::table('archivetest_items')->value('groupId'));
});

it('does nothing if the table doesn’t exist', function () {
    (new class extends Migration {})->archiveTableIfExists('archivetest_items');

    expect(Schema::hasTable('archivetest_items_old'))->toBeFalse();
});

it('keeps archived names within the database’s length limit, including the table prefix', function () {
    $connection = DB::connection();
    $originalPrefix = $connection->getTablePrefix();
    $connection->setTablePrefix(str_repeat('p', 44).'_');

    $archivedNames = match ($connection->getDriverName()) {
        'mysql', 'mariadb' => ['archivetest_ite_old', 'archivetest_it_old1', 'archivetest_gro_old', 'archivetest_gr_old1'],
        'pgsql' => ['archivetest_it_old', 'archivetest_i_old1', 'archivetest_gr_old', 'archivetest_g_old1'],
        default => ['archivetest_items_old', 'archivetest_items_old1', 'archivetest_groups_old', 'archivetest_groups_old1'],
    };

    try {
        $migration = new class extends Migration {};

        for ($i = 1; $i <= 3; $i++) {
            foreach (['archivetest_items', 'archivetest_groups'] as $table) {
                $migration->archiveTableIfExists($table);
            }

            Schema::create('archivetest_groups', function (Blueprint $table) {
                $table->id();
            });

            Schema::create('archivetest_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('groupId')->nullable()->constrained('archivetest_groups', indexName: 'items_group_fk');
                $table->string('name')->unique('items_name_unique');
                $table->index(['groupId', 'name'], 'items_group_name_index');
            });

            DB::table('archivetest_items')->insert(['name' => "Item $i"]);
        }

        expect(DB::table($archivedNames[0])->value('name'))->toBe('Item 1')
            ->and(DB::table($archivedNames[1])->value('name'))->toBe('Item 2')
            ->and(DB::table('archivetest_items')->value('name'))->toBe('Item 3');
    } finally {
        foreach ([...$archivedNames, 'archivetest_items', 'archivetest_groups'] as $table) {
            Schema::dropIfExists($table);
        }

        $connection->setTablePrefix($originalPrefix);
    }
});
