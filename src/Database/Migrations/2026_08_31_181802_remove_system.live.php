<?php

use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ProjectConfig::remove('system.live');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ProjectConfig::set('system.live', true);
    }
};
