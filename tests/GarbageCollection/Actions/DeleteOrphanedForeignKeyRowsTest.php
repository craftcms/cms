<?php

use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedForeignKeyRows;

beforeEach(function () {
    $this->markTestSkippedWhen(
        DB::connection()->getDriverName() === 'pgsql',
        'Postgres check foreign key constraints differently.'
    );

    // Create test tables
    Schema::create('test_authors', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('test_posts', function ($table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->string('title');
    });

    Schema::disableForeignKeyConstraints();
});

afterEach(function () {
    Schema::dropIfExists('test_posts');
    Schema::dropIfExists('test_authors');
    Schema::enableForeignKeyConstraints();
});

test('it deletes orphaned child rows when parent is missing', function () {
    // Create author + post
    $authorId = DB::table('test_authors')->insertGetId(['name' => 'Jane']);
    DB::table('test_posts')->insert([
        'author_id' => $authorId,
        'title' => 'Post 1',
    ]);

    // Delete the parent manually
    DB::table('test_authors')->delete($authorId);

    expect(DB::table('test_posts')->count())->toBe(1);

    // Add the constraint after
    Schema::table('test_posts', function ($table) {
        $table->foreign('author_id')->references('id')->on('test_authors')->onDelete('cascade');
    });

    // Run the cascade cleaner manually (simulating Craft’s behavior)
    app(DeleteOrphanedForeignKeyRows::class)();

    // Assert orphaned posts are deleted
    expect(DB::table('test_posts')->count())->toBe(0);
});

test('it keeps non-orphaned child rows', function () {
    // Create two authors
    $author1 = DB::table('test_authors')->insertGetId(['name' => 'Alice']);
    $author2 = DB::table('test_authors')->insertGetId(['name' => 'Bob']);

    // Posts for both
    DB::table('test_posts')->insert([
        ['author_id' => $author1, 'title' => 'A1'],
        ['author_id' => $author2, 'title' => 'B1'],
    ]);

    // Delete one author
    DB::table('test_authors')->delete($author1);

    // Add the constraint after
    Schema::table('test_posts', function ($table) {
        $table->foreign('author_id')->references('id')->on('test_authors')->onDelete('cascade');
    });

    // Run cleaner
    app(DeleteOrphanedForeignKeyRows::class)();

    // Only Bob’s post should remain
    $remainingPosts = DB::table('test_posts')->pluck('title');
    expect($remainingPosts)->toContain('B1')->not->toContain('A1');
});
