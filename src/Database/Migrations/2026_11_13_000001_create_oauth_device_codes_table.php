<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(LaravelMigrations::class)->ensurePassportTables();
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::OAUTH_DEVICE_CODES);
    }
};
