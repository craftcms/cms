<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\errors\MigrationException;
use craft\events\ModelEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\ArrayHelper;
use craft\i18n\PhpMessageSource;
use craft\web\Controller;
use craft\web\View;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\base\Module;

/**
 * Plugin is the base class for classes representing plugins in terms of objects.
 *
 * @property string $handle The plugin’s handle (alias of [[id]])
 * @property MigrationManager $migrator The plugin’s migration manager
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Plugin extends Module implements PluginInterface
{
    use PluginTrait;

    /**
     * @event ModelEvent The event that is triggered before the plugin’s settings are saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the plugin’s settings from saving.
     *
     * @since 3.0.16
     */
    const EVENT_BEFORE_SAVE_SETTINGS = 'beforeSaveSettings';

    /**
     * @event \yii\base\Event The event that is triggered after the plugin’s settings are saved.
     * @since 3.0.16
     */
    const EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            'standard',
        ];
    }

    /**
     * @var Model|bool|null The model used to store the plugin’s settings
     * @see getSettings()
     */
    private $_settings;

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        // Set some things early in case there are any settings, and the settings model's
        // init() method needs to call Craft::t() or Plugin::getInstance().

        $this->t9nCategory = ArrayHelper::remove($config, 't9nCategory', $this->t9nCategory ?? $id);
        $this->sourceLanguage = ArrayHelper::remove($config, 'sourceLanguage', $this->sourceLanguage);

        if (($basePath = ArrayHelper::remove($config, 'basePath')) !== null) {
            $this->setBasePath($basePath);
        }

        // Translation category
        $i18n = Craft::$app->getI18n();
        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (!isset($i18n->translations[$this->t9nCategory]) && !isset($i18n->translations[$this->t9nCategory . '*'])) {
            $i18n->translations[$this->t9nCategory] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $this->sourceLanguage,
                'basePath' => $this->getBasePath() . DIRECTORY_SEPARATOR . 'translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                $e->roots[$this->id] = $baseDir;
            }
        });

        // Set this as the global instance of this plugin class
        static::setInstance($this);

        // Set the default controller namespace
        if ($this->controllerNamespace === null && ($pos = strrpos(static::class, '\\')) !== false) {
            $namespace = substr(static::class, 0, $pos);
            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                $this->controllerNamespace = $namespace . '\\console\\controllers';
            } else {
                $this->controllerNamespace = $namespace . '\\controllers';
            }
        }

        parent::__construct($id, $parent, $config);
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): string
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
        if (($migration = $this->createInstallMigration()) !== null) {
            try {
                $migrator->migrateUp($migration);
            } catch (MigrationException $e) {
                return false;
            }
        }

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        $this->isInstalled = true;

        $this->afterInstall();

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

        if (($migration = $this->createInstallMigration()) !== null) {
            try {
                $this->getMigrator()->migrateDown($migration);
            } catch (MigrationException $e) {
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
        if ($this->_settings === null) {
            $this->_settings = $this->createSettingsModel() ?: false;
        }

        return $this->_settings ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setSettings(array $settings)
    {
        if (($model = $this->getSettings()) === null) {
            Craft::warning('Attempting to set settings on a plugin that doesn\'t have settings: ' . $this->id);
            return;
        }

        $model->setAttributes($settings, false);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        $view = Craft::$app->getView();
        $settingsHtml = $view->namespaceInputs(function() {
            return (string)$this->settingsHtml();
        }, 'settings');

        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('settings/plugins/_settings', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
        ]);
    }

    /**
     * @inheritdoc
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
        $ret = [
            'label' => $this->name,
            'url' => $this->id,
        ];

        if (($iconPath = $this->cpNavIconPath()) !== null) {
            $ret['icon'] = $iconPath;
        }

        return $ret;
    }

    // Editions
    // -------------------------------------------------------------------------

    /**
     * Compares the active edition with the given edition.
     *
     * @param string $edition The edition to compare the active edition against
     * @param string $operator The comparison operator to use. `=` by default,
     * meaning the method will return `true` if the active edition is equal to
     * the passed-in edition.
     * @return bool
     * @throws InvalidArgumentException if `$edition` is an unsupported edition,
     * or if `$operator` is an invalid operator.
     * @since 3.1.0
     */
    public function is(string $edition, string $operator = '='): bool
    {
        $editions = static::editions();
        $activeIndex = array_search($this->edition, $editions, true);
        $otherIndex = array_search($edition, $editions, true);

        if ($otherIndex === false) {
            throw new InvalidArgumentException('Unsupported edition: ' . $edition);
        }

        switch ($operator) {
            case '<':
            case 'lt':
                return $activeIndex < $otherIndex;
            case '<=':
            case 'le':
                return $activeIndex <= $otherIndex;
            case '>':
            case 'gt':
                return $activeIndex > $otherIndex;
            case '>=':
            case 'ge':
                return $activeIndex >= $otherIndex;
            case '==':
            case '=':
            case 'eq':
                return $activeIndex == $otherIndex;
            case '!=':
            case '<>':
            case 'ne':
                return $activeIndex != $otherIndex;
            default:
                throw new InvalidArgumentException('Invalid edition comparison operator: ' . $operator);
        }
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSaveSettings(): bool
    {
        // Trigger a 'beforeSaveSettings' event
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_SAVE_SETTINGS, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSaveSettings()
    {
        // Trigger an 'afterSaveSettings' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SETTINGS);
        }
    }

    /**
     * Instantiates and returns the plugin’s installation migration, if it has one.
     *
     * @return Migration|null The plugin’s installation migration
     */
    protected function createInstallMigration()
    {
        // See if there's an Install migration in the plugin’s migrations folder
        $migrator = $this->getMigrator();
        $path = $migrator->migrationPath . DIRECTORY_SEPARATOR . 'Install.php';

        if (!is_file($path)) {
            return null;
        }

        require_once $path;
        $class = $migrator->migrationNamespace . '\\Install';

        return new $class();
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
     * Performs actions before the plugin is uninstalled.
     *
     * @return bool Whether the plugin should be uninstalled
     */
    protected function beforeUninstall(): bool
    {
        return true;
    }

    /**
     * Performs actions after the plugin is uninstalled.
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
     * Returns the path to the SVG icon that should be used in the plugin’s nav item in the control panel.
     *
     * @return string|null
     * @see getCpNavItem()
     */
    protected function cpNavIconPath()
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'icon-mask.svg';

        return is_file($path) ? $path : null;
    }
}
