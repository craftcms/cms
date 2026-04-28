<?php

namespace CraftCms\Yii2Adapter\Database;

use CraftCms\Cms\Database\Migration;

class MigrationWrapper extends Migration
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

        ob_start();

        $this->migration->up();

        $output = ob_get_clean();

        $this->output->write($output);
    }

    public function down(): void
    {
        if (!method_exists($this->migration, 'down')) {
            return;
        }

        ob_start();

        $this->migration->down();

        $output = ob_get_clean();

        $this->output->write($output);
    }
}
