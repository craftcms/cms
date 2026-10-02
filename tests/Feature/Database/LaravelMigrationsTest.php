<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('migration table track column upgrade preserves existing rows', function () {
    Schema::dropIfExists('migrations');
    Schema::create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });

    DB::table('migrations')->insert([
        'migration' => '2026_01_25_114732_create_jobs_table',
        'batch' => 1,
    ]);

    app(LaravelMigrations::class)->ensureMigrationTableTrackColumn();

    expect(Schema::hasColumn('migrations', 'track'))->toBeTrue()
        ->and(DB::table('migrations')->where('migration', '2026_01_25_114732_create_jobs_table')->exists())->toBeTrue();
});

test('migration table format upgrade converts yii era rows', function () {
    Schema::dropIfExists('migrations');
    Schema::create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('track');
        $table->string('name');
        $table->dateTime('applyTime');
    });

    DB::table('migrations')->insert([
        ['track' => 'craft', 'name' => 'm260401_155236_min_authors_setting', 'applyTime' => now()],
        ['track' => 'plugin:ckeditor', 'name' => 'm260427_230945_references', 'applyTime' => now()],
        ['track' => 'content', 'name' => 'm250101_000000_some_content_migration', 'applyTime' => now()],
    ]);

    app(LaravelMigrations::class)->ensureMigrationTableFormat();

    expect(Schema::hasColumn('migrations', 'migration'))->toBeTrue()
        ->and(DB::table('migrations')->count())->toBe(3)
        ->and(DB::table('migrations')->where('track', 'craft')->value('migration'))->toBe('2026_04_01_155236_min_authors_setting')
        ->and(DB::table('migrations')->where('track', 'plugin:ckeditor')->value('migration'))->toBe('m260427_230945_references')
        ->and(DB::table('migrations')->whereNull('track')->value('migration'))->toBe('m250101_000000_some_content_migration');
});

test('migration table format upgrade leaves a laravel table alone', function () {
    Schema::dropIfExists('migrations');
    Schema::create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('track')->nullable();
        $table->string('migration');
        $table->integer('batch');
    });

    DB::table('migrations')->insert([
        'track' => 'craft',
        'migration' => '2026_04_01_155236_min_authors_setting',
        'batch' => 3,
    ]);

    app(LaravelMigrations::class)->ensureMigrationTableFormat();

    expect(DB::table('migrations')->where('migration', '2026_04_01_155236_min_authors_setting')->value('batch'))->toBe(3);
});
