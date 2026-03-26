<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\File;
use ReflectionClass;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait Installable
{
    /** @var bool Whether the plugin is currently installed. (Will only be false when a plugin is currently being installed.) */
    public bool $isInstalled = false;

    private ?Migrator $migrator = null;

    public function install(): void
    {
        $this->beforeInstall();

        $migrator = $this->getMigrator();

        if ($installMigration = $this->createInstallMigration()) {
            $migrator->runMigration($installMigration, 'up');
            $migrator->getRepository()->log('Install', 1);
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getPendingMigrations() as $file) {
            $migrator->getRepository()->log($migrator->getMigrationName($file), 1);
        }

        $this->isInstalled = true;

        $this->afterInstall();
    }

    public function uninstall(): void
    {
        $this->beforeUninstall();

        $installPath = $this->getMigrationsPath().'/Install.php';

        if (File::exists($installPath)) {
            $this->getMigrator()->resetMigrations(
                [$installPath],
                [dirname($installPath)],
            );
        }

        $this->afterUninstall();
    }

    public function createInstallMigration(): ?object
    {
        $path = $this->getMigrationsPath().'/Install.php';

        if (! File::exists($path)) {
            return null;
        }

        $realPath = realpath($path);

        $migration = $this->findMigrationClassByFile($realPath);

        if ($migration !== null) {
            return $migration;
        }

        $migration = require $path;

        if (is_object($migration)) {
            return $migration;
        }

        return $this->findMigrationClassByFile($realPath);
    }

    private function findMigrationClassByFile(string $realPath): ?object
    {
        foreach (array_reverse(get_declared_classes()) as $class) {
            if ($realPath !== new ReflectionClass($class)->getFileName()) {
                continue;
            }

            return app()->make($class);
        }

        return null;
    }

    public function getMigrator(): Migrator
    {
        return $this->migrator ?? $this->migrator = app(Migrator::class)
            ->track("plugin:$this->handle")
            ->setPaths([$this->getMigrationsPath()]);
    }

    /**
     * Performs actions before the plugin is installed.
     */
    protected function beforeInstall(): void {}

    /**
     * Performs actions after the plugin is installed.
     */
    protected function afterInstall(): void {}

    /**
     * Performs actions before the plugin is uninstalled.
     */
    protected function beforeUninstall(): void {}

    /**
     * Performs actions after the plugin is uninstalled.
     */
    protected function afterUninstall(): void {}
}
