<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Facades\File;

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

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function uninstall(): void
    {
        $this->beforeUninstall();

        if (File::exists($this->getBasePath().'/migrations/Install.php')) {
            $this->getMigrator()->resetMigrations(
                [$this->getBasePath().'/migrations/Install.php'],
                [$this->getBasePath().'/migrations'],
            );
        }

        $this->afterUninstall();
    }

    public function createInstallMigration(): ?object
    {
        if (! File::exists($this->getBasePath().'/migrations/Install.php')) {
            return null;
        }

        $namespace = substr(static::class, 0, strrpos(static::class, '\\'));
        $class = $namespace.'\migrations\Install';

        return app()->make($class);
    }

    /** {@inheritdoc} */
    public function getMigrator(): Migrator
    {
        return $this->migrator ?? $this->migrator = resolve(Migrator::class)
            ->track("plugin:$this->handle")
            ->setPaths([
                $this->getBasePath().'/migrations',
            ]);
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
