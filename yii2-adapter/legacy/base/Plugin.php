<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\events\ModelEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\Controller;
use craft\web\View;
use CraftCms\Cms\Plugin\Concerns\HasEditions;
use CraftCms\Cms\Plugin\Concerns\Installable;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Database\MigrationWrapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use yii\base\Event;
use yii\base\Module;
use yii\web\Response;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;

/**
 * Plugin is the base class for classes representing plugins in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Plugin\Plugin} instead.
 */
class Plugin extends Module implements PluginInterface
{
    use PluginTrait;
    use HasEditions;
    use Installable;

    /**
     * @event ModelEvent The event that is triggered before the plugin’s settings are saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the plugin’s settings from saving.
     *
     * @since 3.0.16
     */
    public const string EVENT_BEFORE_SAVE_SETTINGS = 'beforeSaveSettings';

    /**
     * @event \yii\base\Event The event that is triggered after the plugin’s settings are saved.
     * @since 3.0.16
     */
    public const string EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

    /**
     * Additional config the plugin should be instantiated with
     */
    public static function config(): array
    {
        return [];
    }

    /**
     * @var Model|bool|null The model used to store the plugin’s settings
     * @see getSettings()
     */
    private bool|null|Model $_settings = null;

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        $this->handle = $id;
        $this->version = $this->getVersion();

        // Set some things early in case there are any settings, and the settings model's
        // init() method needs to call t() or Plugin::getInstance().

        $this->t9nCategory = Arr::pull($config, 't9nCategory', $this->t9nCategory ?? $id);
        $this->sourceLanguage = Arr::pull($config, 'sourceLanguage', $this->sourceLanguage);

        if (($basePath = Arr::pull($config, 'basePath')) !== null) {
            $this->setBasePath($basePath);
        }

        // Translation category
        $pluginMessageSource = new MessageSource($this->getBasePath() . DIRECTORY_SEPARATOR . 'translations');
        $formatter = new IntlMessageFormatter();
        $category = new CategorySource(
            name: $this->t9nCategory,
            reader: $pluginMessageSource,
            formatter: $formatter,
        );

        I18N::addCategorySources($category);

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                $e->roots[$this->id] = $baseDir;
            }
        });

        // Set this as the global instance of this plugin class
        static::setInstance($this);

        // Set the default controller namespace
        if (!isset($this->controllerNamespace) && ($pos = strrpos(static::class, '\\')) !== false) {
            $namespace = substr(static::class, 0, $pos);
            if (app()->runningInConsole()) {
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
    public function init()
    {
        parent::init();

        // Set $hasReadOnlyCpSettings to true if we're using the default getSettingsResponse()
        if (
            $this->hasCpSettings &&
            !$this->hasReadOnlyCpSettings &&
            (new ReflectionMethod($this, 'getSettingsResponse'))->getDeclaringClass()->name === self::class
        ) {
            $this->hasReadOnlyCpSettings = true;
        }
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
    public function getSettings(): ?Model
    {
        if (!isset($this->_settings)) {
            $this->_settings = $this->createSettingsModel() ?: false;
        }

        return $this->_settings ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setSettings(array $settings): void
    {
        if (($model = $this->getSettings()) === null) {
            Log::warning('Attempting to set settings on a plugin that doesn\'t have settings: ' . $this->id);
            return;
        }

        $model->setAttributes($settings, false);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return $this->settingsResponse(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsResponse(): mixed
    {
        return $this->settingsResponse(true);
    }

    private function settingsResponse(bool $readOnly): Response
    {
        $settingsHtml = InputNamespace::namespaceInputs(function() use ($readOnly) {
            if ($readOnly) {
                // Just return the settings HTML with disabled inputs by default
                return (string)Html::disableInputs(fn() => $this->settingsHtml());
            }

            return (string)$this->settingsHtml();
        }, 'settings');

        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->rendertemplate('settings/plugins/_settings', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
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

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSaveSettings(): bool
    {
        // Fire a 'beforeSaveSettings' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SETTINGS)) {
            $event = new ModelEvent();
            $this->dispatchComponentEvent(self::EVENT_BEFORE_SAVE_SETTINGS, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSaveSettings(): void
    {
        // Fire an 'afterSaveSettings' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SETTINGS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SETTINGS);
        }
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Model|null
     */
    protected function createSettingsModel(): ?Model
    {
        return null;
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content block on the settings page.
     *
     * @return string|null The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        return null;
    }

    /**
     * Returns the path to the SVG icon that should be used in the plugin’s nav item in the control panel.
     *
     * @return string|null
     * @see getCpNavItem()
     */
    protected function cpNavIconPath(): ?string
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'icon-mask.svg';

        return is_file($path) ? $path : null;
    }

    public function getBasePath(): string
    {
        return parent::getBasePath();
    }

    /** {@inheritdoc} */
    public static function create(array $config): PluginInterface
    {
        // Merge in the plugin’s dynamic config
        $config = Arr::merge($config, static::config());

        $config['class'] = static::class;

        // Load legacy Craft if it hadn't yet loaded
        app('Craft');

        return Craft::createObject($config, [$config['handle'], Craft::$app]);
    }

    public function createInstallMigration(): ?object
    {
        if (!File::exists($this->getBasePath() . '/migrations/Install.php')) {
            return null;
        }

        $namespace = substr(static::class, 0, strrpos(static::class, '\\'));
        $class = $namespace . '\migrations\Install';

        if (!is_a($class, Migration::class, true)) {
            return new MigrationWrapper($class);
        }

        return app()->make($class);
    }

    public static function getInstance(): PluginInterface
    {
        return parent::getInstance();
    }
}
