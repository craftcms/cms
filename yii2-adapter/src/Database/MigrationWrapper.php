<?php

namespace CraftCms\Yii2Adapter\Database;

use CraftCms\Cms\Database\Migration;

final class MigrationWrapper extends Migration
{
    private object $migration;

    public function __construct(
        string $migrationClass,
    ) {
        parent::__construct();

        $this->migration = app()->make($migrationClass);
    }

    public function up(): void
    {
        if (!method_exists($this->migration, 'up')) {
            return;
        }

        $this->migration->up();
    }

    public function down(): void
    {
        if (!method_exists($this->migration, 'down')) {
            return;
        }

        $this->migration->down();
    }
}
