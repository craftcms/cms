<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Support\Facades\ProjectConfig;

return new class extends Migration
{
    public function up(): void
    {
        if (ProjectConfig::get('email') !== null) {
            ProjectConfig::remove('email', 'Remove legacy email settings.');
        }
    }

    public function down(): void {}
};
