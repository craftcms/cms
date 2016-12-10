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

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasCpSection()
    {
        return false;
    }

    // Properties
    // =========================================================================

    /**
     * @var Model|boolean The model used to store the plugin’s settings
     * @see getSettingsModel()
     */
    private $_settingsModel;

    /**
     * @var string The plugin’s base path
     */
    private $_basePath;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set up a translation message source for the plugin
        $i18n = Craft::$app->getI18n();
        $handle = $this->getHandle();

        if (!isset($i18n->translations[$handle]) && !isset($i18n->translations[$handle.'*'])) {
            $i18n->translations[$handle] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $this->sourceLanguage,
                'basePath' => "@plugins/$handle/translations",
                'allowOverrides' => true,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function getHandle()
    {
        return $this->id;
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

        if ($migration !== null) {
            if ($migrator->migrateUp($migration) === false) {
                return false;
            }
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
    public function update($fromVersion)
    {
        if ($this->beforeUpdate() === false) {
            return false;
        }

        if ($this->getMigrator()->up() === false) {
            return false;
        }

        $this->afterUpdate();

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

        if ($migration !== null) {
            if ($this->getMigrator()->migrateDown($migration) === false) {
                return false;
            }
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
    public function getSettingsResponse()
    {
        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('settings/plugins/_settings',
            [
                'plugin' => $this,
                'settingsHtml' => $this->getSettingsHtml()
            ]);
    }

    /**
     * Returns the plugin’s migration manager
     *
     * @return MigrationManager The plugin’s migration manager
     */
    public function getMigrator()
    {
        /** @var MigrationManager $migrator */
        $migrator = $this->get('migrator');

        return $migrator;
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
     * @return boolean Whether the plugin should be installed
     */
    protected function beforeInstall()
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
     * @return boolean Whether the plugin should be updated
     */
    protected function beforeUpdate()
    {
        return true;
    }

    /**
     * Performs actions after the plugin is updated.
     */
    protected function afterUpdate()
    {
    }

    /**
     * Performs actions before the plugin is installed.
     *
     * @return boolean Whether the plugin should be installed
     */
    protected function beforeUninstall()
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
     * @return string The rendered settings HTML
     */
    protected function getSettingsHtml()
    {
        return null;
    }
}
