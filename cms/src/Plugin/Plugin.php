<?php

namespace CraftCms\Cms\Plugin;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\Html;
use craft\i18n\PhpMessageSource;
use craft\web\Controller;
use craft\web\View;
use CraftCms\Cms\Component\Concerns\HasComponentEvents;
use CraftCms\Cms\Component\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Component\Events\ComponentEvent;
use CraftCms\Cms\Plugin\Concerns\PluginTrait;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReflectionClass;
use yii\web\Response;

abstract class Plugin implements PluginInterface
{
    use HasComponentEvents;
    use PluginTrait;

    /**
     * @event ComponentEvent The event that is triggered before the plugin’s settings are saved.
     *
     * You may set {@see ComponentEvent::$isValid} to `false` to prevent the plugin’s settings from saving.
     */
    public const string EVENT_BEFORE_SAVE_SETTINGS = 'beforeSaveSettings';

    /**
     * @event ComponentEvent The event that is triggered after the plugin’s settings are saved.
     */
    public const string EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

    /**
     * @var ValidatableComponentInterface|bool|null The model used to store the plugin’s settings
     *
     * @see getSettings()
     */
    private bool|null|ValidatableComponentInterface $settings = null;

    private ?MigrationManager $migrator = null;

    public function __construct(
        public string $handle,
        protected ?string $basePath = null,
    ) {
        $this->t9nCategory ??= $this->handle;

        // Translation category
        $i18n = Craft::$app->getI18n();

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (! isset($i18n->translations[$this->t9nCategory]) && ! isset($i18n->translations[$this->t9nCategory.'*'])) {
            $i18n->translations[$this->t9nCategory] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $this->sourceLanguage,
                'basePath' => $this->getBasePath().DIRECTORY_SEPARATOR.'translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        // Base template directory
        \craft\base\Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath().DIRECTORY_SEPARATOR.'templates')) {
                $e->roots[$this->handle] = $baseDir;
            }
        });
    }

    /** {@inheritdoc} */
    public static function editions(): array
    {
        return [
            'standard',
        ];
    }

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
            'migrationPath' => $this->getBasePath().DIRECTORY_SEPARATOR.'migrations',
        ]);

        return $this->migrator;
    }

    /** {@inheritdoc} */
    public function getSettings(): ?ValidatableComponentInterface
    {
        if (! isset($this->settings)) {
            $this->settings = $this->createSettingsModel() ?: false;
        }

        return $this->settings ?: null;
    }

    /** {@inheritdoc} */
    public function setSettings(array $settings): void
    {
        if (($model = $this->getSettings()) === null) {
            Log::warning('Attempting to set settings on a plugin that doesn\'t have settings: '.$this->handle);

            return;
        }

        $model->setAttributes($settings);
    }

    /** {@inheritdoc} */
    public function getSettingsResponse(): mixed
    {
        return $this->settingsResponse(false);
    }

    /** {@inheritdoc} */
    public function getReadOnlySettingsResponse(): mixed
    {
        return $this->settingsResponse(true);
    }

    private function settingsResponse(bool $readOnly): Response
    {
        $view = Craft::$app->getView();
        $settingsHtml = $view->namespaceInputs(function () use ($readOnly) {
            if ($readOnly) {
                // Just return the settings HTML with disabled inputs by default
                return (string) Html::disableInputs(fn () => $this->settingsHtml());
            }

            return (string) $this->settingsHtml();
        }, 'settings');

        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        return $controller->renderTemplate('settings/plugins/_settings.twig', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
            'readOnly' => $readOnly,
        ]);
    }

    /** {@inheritdoc} */
    public function getCpNavItem(): ?array
    {
        $ret = [
            'label' => $this->name,
            'url' => $this->handle,
        ];

        if (($iconPath = $this->cpNavIconPath()) !== null) {
            $ret['icon'] = $iconPath;
        }

        return $ret;
    }

    /** {@inheritdoc} */
    public function is(string $edition, string $operator = '='): bool
    {
        $editions = static::editions();
        $activeIndex = array_search($this->edition, $editions, true);
        $otherIndex = array_search($edition, $editions, true);

        if ($otherIndex === false) {
            throw new InvalidArgumentException('Unsupported edition: '.$edition);
        }

        return match ($operator) {
            '<', 'lt' => $activeIndex < $otherIndex,
            '<=', 'le' => $activeIndex <= $otherIndex,
            '>', 'gt' => $activeIndex > $otherIndex,
            '>=', 'ge' => $activeIndex >= $otherIndex,
            '==', '=', 'eq' => $activeIndex === $otherIndex,
            '!=', '<>', 'ne' => $activeIndex !== $otherIndex,
            default => throw new InvalidArgumentException('Invalid edition comparison operator: '.$operator),
        };
    }

    /** {@inheritdoc} */
    public function beforeSaveSettings(): bool
    {
        if (Event::hasListeners(self::componentEventName(self::EVENT_BEFORE_SAVE_SETTINGS))) {
            Event::dispatch(self::componentEventName(self::EVENT_BEFORE_SAVE_SETTINGS), $event = new ComponentEvent($this));

            return $event->isValid;
        }

        return true;
    }

    /** {@inheritdoc} */
    public function afterSaveSettings(): void
    {
        if (Event::hasListeners(self::componentEventName(self::EVENT_AFTER_SAVE_SETTINGS))) {
            Event::dispatch(self::componentEventName(self::EVENT_AFTER_SAVE_SETTINGS), new ComponentEvent($this));
        }
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
        $path = $migrator->migrationPath.DIRECTORY_SEPARATOR.'Install.php';

        if (! is_file($path)) {
            return null;
        }

        require_once $path;
        $class = $migrator->migrationNamespace.'\\Install';

        return new $class;
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

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?ValidatableComponentInterface
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
     * @see getCpNavItem()
     */
    protected function cpNavIconPath(): ?string
    {
        $path = $this->getBasePath().DIRECTORY_SEPARATOR.'icon-mask.svg';

        return is_file($path) ? $path : null;
    }

    /** {@inheritdoc} */
    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            $class = new ReflectionClass($this);
            $this->basePath = dirname($class->getFileName());
        }

        return $this->basePath;
    }

    /** {@inheritdoc} */
    public static function create(array $config): PluginInterface
    {
        app()->singleton(static::class, function () use ($config) {
            $plugin = new static($config['handle'], $config['basePath'] ?? null);

            foreach ($config as $key => $value) {
                if (property_exists($plugin, $key)) {
                    $plugin->{$key} = $value;
                }
            }

            return $plugin;
        });

        return static::getInstance();
    }

    /** {@inheritdoc} */
    public static function getInstance(): PluginInterface
    {
        return app(static::class);
    }
}
