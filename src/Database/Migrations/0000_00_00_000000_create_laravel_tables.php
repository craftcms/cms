<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LaravelMigrations::class)->ensureTables();
    }

    public function down(): void {}
};
