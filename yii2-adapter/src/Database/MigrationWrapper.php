<?php

namespace CraftCms\Yii2Adapter\Database;

use craft\db\Migration as LegacyMigration;
use CraftCms\Cms\Database\Migration;
use RuntimeException;

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
        $this->run('up');
    }

    public function down(): void
    {
        $this->run('down');
    }

    /**
     * Runs the wrapped migration’s `up()` or `down()` method.
     *
     * Yii migrations catch their own exceptions and return `false` instead, so
     * this rethrows them to stop Laravel’s migrator from logging the migration
     * as successful.
     */
    private function run(string $method): void
    {
        if (!method_exists($this->migration, $method)) {
            return;
        }

        ob_start();

        try {
            $result = $this->migration instanceof LegacyMigration
                ? $this->migration->$method(true)
                : $this->migration->$method();
        } finally {
            $this->output->write(ob_get_clean());
        }

        if ($result === false) {
            throw new RuntimeException(sprintf('Migration %s::%s() failed.', $this->migration::class, $method));
        }
    }
}
