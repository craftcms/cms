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
