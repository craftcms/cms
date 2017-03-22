<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\i18n\PhpMessageSource;
use craft\web\Controller;
use yii\base\Module;

/**
 * Plugin is the base class for classes representing plugins in terms of objects.
 *
 * @property MigrationManager $migrator The plugin’s migration manager
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Plugin extends Module implements PluginInterface
{
    // Traits
    // =========================================================================

    use PluginTrait;

    // Properties
    // =========================================================================

    /**
     * @var Model|bool|null The model used to store the plugin’s settings
     * @see getSettingsModel()
     */
    private $_settingsModel;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->t9nCategory === null) {
            $this->t9nCategory = strtolower($this->handle);
        }

        // Set up a translation message source for the plugin
        $i18n = Craft::$app->getI18n();

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (!isset($i18n->translations[$this->t9nCategory]) && !isset($i18n->translations[$this->t9nCategory.'*'])) {
            $i18n->translations[$this->t9nCategory] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $this->sourceLanguage,
                'basePath' => $this->getBasePath().'/translations',
                'allowOverrides' => true,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        if ($this->beforeInstall() === false) {
            return false;
        }

        $migrator = $this->getMigrator();

        // Run the install migration, if there is one
        $migration = $this->createInstallMigration();

        if ($migration !== null && $migrator->migrateUp($migration) === false) {
            return false;
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        $this->afterInstall();

        return null;
    }

    /**
     * @inheritdoc
     */
    public function update(string $fromVersion)
    {
        if ($this->beforeUpdate($fromVersion) === false) {
            return false;
        }

        if ($this->getMigrator()->up() === false) {
            return false;
        }

        $this->afterUpdate($fromVersion);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        if ($this->beforeUninstall() === false) {
            return false;
        }

        $migration = $this->createInstallMigration();

        if ($migration !== null && $this->getMigrator()->migrateDown($migration) === false) {
            return false;
        }

        $this->afterUninstall();

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSettings()
    {
        if ($this->_settingsModel === null) {
            $this->_settingsModel = $this->createSettingsModel() ?: false;
        }

        if ($this->_settingsModel !== false) {
            return $this->_settingsModel;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setSettings(array $settings)
    {
        $this->getSettings()->setAttributes($settings, false);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('settings/plugins/_settings', [
            'plugin' => $this,
            'settingsHtml' => $this->settingsHtml()
        ]);
    }

    /**
     * Returns the plugin’s migration manager
     *
     * @return MigrationManager The plugin’s migration manager
     */
    public function getMigrator(): MigrationManager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('migrator');
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        if (($iconPath = $this->cpNavIconPath()) !== null) {
            $iconSvg = file_get_contents($iconPath);
        } else {
            $iconSvg = false;
        }

        return [
            'label' => $this->name,
            'url' => $this->id,
            'iconSvg' => $iconSvg
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineTemplateComponent()
    {
        return null;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Instantiates and returns the plugin’s installation migration, if it has one.
     *
     * @return Migration|null The plugin’s installation migration
     */
    protected function createInstallMigration()
    {
        // See if there's an Install migration in the plugin’s migrations folder
        $migrator = $this->getMigrator();
        $path = $migrator->migrationPath.DIRECTORY_SEPARATOR.'Install.php';

        if (!is_file($path)) {
            return null;
        }

        require_once $path;
        $class = $migrator->migrationNamespace.'\\Install';

        return new $class;
    }

    /**
     * Performs actions before the plugin is installed.
     *
     * @return bool Whether the plugin should be installed
     */
    protected function beforeInstall(): bool
    {
        return true;
    }

    /**
     * Performs actions after the plugin is installed.
     */
    protected function afterInstall()
    {
    }

    /**
     * Performs actions before the plugin is updated.
     *
     * @param string $fromVersion The previously installed version of the plugin.
     *
     * @return bool Whether the plugin should be updated
     */
    protected function beforeUpdate(string $fromVersion): bool
    {
        return true;
    }

    /**
     * Performs actions after the plugin is updated.
     *
     * @param string $fromVersion The previously installed version of the plugin.
     */
    protected function afterUpdate(string $fromVersion)
    {
    }

    /**
     * Performs actions before the plugin is installed.
     *
     * @return bool Whether the plugin should be installed
     */
    protected function beforeUninstall(): bool
    {
        return true;
    }

    /**
     * Performs actions after the plugin is installed.
     */
    protected function afterUninstall()
    {
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Model|null
     */
    protected function createSettingsModel()
    {
        return null;
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content block on the settings page.
     *
     * @return string|null The rendered settings HTML
     */
    protected function settingsHtml()
    {
        return null;
    }

    /**
     * Returns the path to the SVG icon that should be used in the plugin’s CP nav item.
     *
     * @return string|null
     * @see getCpNavItem()
     */
    protected function cpNavIconPath()
    {
        $path = $this->getBasePath().DIRECTORY_SEPARATOR.'icon-mask.svg';

        return is_file($path) ? $path : null;
    }
}
