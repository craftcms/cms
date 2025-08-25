<?php

namespace CraftCms\Cms\Plugin\Concerns;

use craft\db\Migration;
use craft\db\MigrationManager;
use CraftCms\Cms\Plugin\Plugin;
use ReflectionClass;

/**
 * @mixin Plugin
 *
 * @internal
 *
 * @since 6.0.0
 */
trait Installable
{
    /** @var bool Whether the plugin is currently installed. (Will only be false when a plugin is currently being installed.) */
    public bool $isInstalled = false;

    private ?MigrationManager $migrator = null;

    /** {@inheritdoc} */
    public function install(): void
    {
        $this->beforeInstall();

        $migrator = $this->getMigrator();

        // Run the install migration, if there is one
        if (($migration = $this->createInstallMigration()) !== null) {
            $migrator->migrateUp($migration);
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        $this->isInstalled = true;

        $this->afterInstall();
    }

    /** {@inheritdoc} */
    public function uninstall(): void
    {
        $this->beforeUninstall();

        if (($migration = $this->createInstallMigration()) !== null) {
            $this->getMigrator()->migrateDown($migration);
        }

        $this->afterUninstall();
    }

    /**
     * Instantiates and returns the plugin’s installation migration, if it has one.
     *
     * @return Migration|null The plugin’s installation migration
     */
    protected function createInstallMigration(): ?Migration
    {
        // See if there's an Install migration in the plugin’s migrations folder
        $migrator = $this->getMigrator();
        $path = $migrator->migrationPath.'/Install.php';

        if (! is_file($path)) {
            return null;
        }

        require_once $path;
        $class = $migrator->migrationNamespace.'\\Install';

        return new $class;
    }

    /** {@inheritdoc} */
    public function getMigrator(): MigrationManager
    {
        if (isset($this->migrator)) {
            return $this->migrator;
        }

        $ref = new ReflectionClass($this);
        $ns = $ref->getNamespaceName();

        $this->migrator = new MigrationManager([
            'track' => "plugin:$this->handle",
            'migrationNamespace' => ($ns ? $ns.'\\' : '').'migrations',
            'migrationPath' => $this->getBasePath().'/migrations',
        ]);

        return $this->migrator;
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
