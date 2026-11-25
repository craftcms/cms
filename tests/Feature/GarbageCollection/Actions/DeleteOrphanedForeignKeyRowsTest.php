<?php

declare(strict_types=1);

use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedForeignKeyRows;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('test_authors', function (Blueprint $table) {
        $table->unsignedBigInteger('id')->primary();
        $table->unsignedInteger('region');
        $table->unique(['id', 'region']);
    });
    Schema::create('test_posts', function (Blueprint $table) {
        $table->unsignedBigInteger('author_id')->nullable();
        $table->unsignedInteger('region')->nullable();
        $table->string('title');
    });

    if (DB::isSqlite()) {
        DB::statement('PRAGMA defer_foreign_keys = ON');
    } else {
        Schema::disableForeignKeyConstraints();
    }
});

afterEach(function () {
    Schema::dropIfExists('test_comments');
    Schema::dropIfExists('test_posts');
    Schema::dropIfExists('test_authors');
    Schema::enableForeignKeyConstraints();
});

test('it deletes orphaned child rows when parent is missing', function () {
    Schema::table('test_posts', fn (Blueprint $table) => $table->unsignedBigInteger('id'));
    DB::table('test_authors')->insert(['id' => 2, 'region' => 1]);
    DB::table('test_posts')->insert([
        ['id' => 99, 'author_id' => 1, 'title' => 'orphan'],
        ['id' => 1, 'author_id' => 2, 'title' => 'valid'],
        ['id' => 2, 'author_id' => null, 'title' => 'null'],
    ]);
    Schema::table('test_posts', function (Blueprint $table) {
        $table->foreign('author_id')->references('id')->on('test_authors')->cascadeOnDelete()->notValid();
    });

    app(DeleteOrphanedForeignKeyRows::class)();

    expect(DB::table('test_posts')->orderBy('id')->pluck('title')->all())->toBe(['valid', 'null']);
});

test('it keeps valid and nullable composite keys without a child id', function () {
    DB::table('test_authors')->insert([['id' => 1, 'region' => 10], ['id' => 2, 'region' => 20]]);
    DB::table('test_posts')->insert([
        ['author_id' => 1, 'region' => 20, 'title' => 'missing tuple'],
        ['author_id' => 1, 'region' => 10, 'title' => 'valid'],
        ['author_id' => null, 'region' => 99, 'title' => 'null author'],
        ['author_id' => 99, 'region' => null, 'title' => 'null region'],
    ]);
    Schema::table('test_posts', function (Blueprint $table) {
        $table->foreign(['author_id', 'region'])->references(['id', 'region'])->on('test_authors')->cascadeOnDelete()->notValid();
    });

    app(DeleteOrphanedForeignKeyRows::class)();

    expect(DB::table('test_posts')->orderBy('title')->pluck('title')->all())->toBe(['null author', 'null region', 'valid']);
});

/**
 * Gives test_posts IDs, and adds test_comments, whose rows reference them.
 */
function createTestComments(Closure $foreignKey): void
{
    Schema::table('test_posts', function (Blueprint $table) {
        $table->unsignedBigInteger('id');
        $table->unique('id');
    });
    Schema::create('test_comments', function (Blueprint $table) use ($foreignKey) {
        $table->unsignedBigInteger('post_id')->nullable();
        $foreignKey($table->foreign('post_id')->references('id')->on('test_posts'));
    });
}

function addTestPostsAuthorForeignKey(): void
{
    Schema::table('test_posts', function (Blueprint $table) {
        $table->foreign('author_id')->references('id')->on('test_authors')->cascadeOnDelete()->notValid();
    });
}

// https://github.com/craftcms/cms/issues/20885
test('it applies the foreign key actions of rows that reference deleted orphans', function () {
    createTestComments(fn ($foreign) => $foreign->nullOnDelete());
    DB::table('test_posts')->insert(['id' => 1, 'author_id' => 99, 'title' => 'orphan']);
    DB::table('test_comments')->insert(['post_id' => 1]);
    addTestPostsAuthorForeignKey();
    Schema::enableForeignKeyConstraints();

    app(DeleteOrphanedForeignKeyRows::class)();

    expect(DB::table('test_posts')->count())->toBe(0)
        ->and(DB::table('test_comments')->count())->toBe(1)
        ->and(DB::table('test_comments')->value('post_id'))->toBeNull();
});

test('it skips orphaned rows it can’t delete, and keeps deleting the rest', function () {
    createTestComments(fn ($foreign) => $foreign->restrictOnDelete());
    foreach (array_chunk(range(1, 1500), 500) as $ids) {
        DB::table('test_posts')->insert(array_map(fn (int $id) => ['id' => $id, 'author_id' => $id, 'title' => "orphan $id"], $ids));
    }
    DB::table('test_comments')->insert(['post_id' => 1]);
    addTestPostsAuthorForeignKey();
    Schema::enableForeignKeyConstraints();

    // MySQL commits the test's transaction when tables are changed, so reopen it for the action's savepoints
    if (! DB::getPdo()->inTransaction()) {
        DB::getPdo()->beginTransaction();
    }

    app(DeleteOrphanedForeignKeyRows::class)();

    expect(DB::table('test_posts')->where('id', 1)->exists())->toBeTrue()
        ->and(DB::table('test_posts')->where('id', '>', 1000)->count())->toBe(0)
        ->and(DB::table('test_comments')->value('post_id'))->toBe(1);
});
