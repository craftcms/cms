<?php

namespace CraftCms\Cms\Plugin;

use Craft;
use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\Html;
use craft\web\Controller;
use CraftCms\Cms\Component\Concerns\HasComponentEvents;
use CraftCms\Cms\Component\Contracts\ValidatableComponentInterface;
use CraftCms\Cms\Component\Events\ComponentEvent;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReflectionClass;
use yii\web\Response;

abstract class Plugin implements PluginInterface
{
    use HasComponentEvents;

    /** @var string The plugin's version */
    public string $version = '1.0.0';

    /** @var string|null The plugin’s package name */
    public ?string $packageName = null;

    /** @var string|null The plugin’s display name */
    public ?string $name = null;

    /** @var string The plugin’s schema version number */
    public string $schemaVersion = '1.0.0';

    /** @var string|null The plugin’s description */
    public ?string $description = null;

    /** @var string|null The plugin developer’s name */
    public ?string $developer = null;

    /** @var string|null The plugin developer’s website URL */
    public ?string $developerUrl = null;

    /** @var string|null The plugin developer’s support email */
    public ?string $developerEmail = null;

    /** @var string|null The plugin’s documentation URL */
    public ?string $documentationUrl = null;

    /**
     * @var string|null The plugin’s changelog URL.
     *
     * The URL should begin with `https://` and point to a plain text Markdown-formatted changelog.
     * Version headers must follow the general format:
     *
     * ```
     * ## X.Y.Z - YYYY-MM-DD
     * ```
     *
     * with the following possible deviations:
     *
     * - other text can come before the version number, like the plugin’s name
     * - a 4th version number is allowed (e.g. `1.2.3.4`)
     * - pre-release versions are allowed (e.g. `1.0.0-alpha.1`)
     * - the version can start with `v` (e.g. `v1.2.3`)
     * - the version can be hyperlinked (e.g. `[1.2.3]`)
     * - dates can use dots as separators, rather than hyphens (e.g. `YYYY.MM.DD`)
     * - a `[CRITICAL]` flag can be appended after the date to indicate a critical release
     *
     * More notes:
     *
     * - Releases should be listed in descending order (newest on top). Craft will stop parsing the changelog as soon as it hits a version that is older than or equal to the installed version.
     * - Any content that does not follow a version header line will be ignored.
     * - For consistency and clarity, release notes should follow [keepachangelog.com](http://keepachangelog.com/), but it’s not enforced.
     * - Release notes can contain notes using the format `> {note} Some note`. `{warning}` and `{tip}` are also supported.
     */
    public ?string $changelogUrl = null;

    /** @var string|null The plugin’s download URL */
    public ?string $downloadUrl = null;

    /** @var string|null The translation category that this plugin’s translation messages should use. Defaults to the lowercased plugin handle. */
    public ?string $t9nCategory = null;

    /** @var string The language that the plugin’s messages were written in */
    public string $sourceLanguage = 'en-US';

    /** @var bool Whether the plugin has a settings page in the control panel */
    public bool $hasCpSettings = false;

    /**
     * @var bool Whether the plugin supports a read-only settings page in the control panel, which
     *           can be shown when admin changes are disallowed.
     */
    public bool $hasReadOnlyCpSettings = false;

    /** @var bool Whether the plugin has its own section in the control panel */
    public bool $hasCpSection = false;

    /** @var bool Whether the plugin is currently installed. (Will only be false when a plugin is currently being installed.) */
    public bool $isInstalled = false;

    /** @var string The minimum required version the plugin has to be so it can be updated. */
    public string $minVersionRequired = '';

    /** @var Edition The minimum required Craft CMS edition. */
    public Edition $minCmsEdition = Edition::Solo;

    /** @var string The active edition. */
    public string $edition = 'standard';

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

    protected ?string $basePath = null;

    public function __construct(
        public string $handle,
    ) {}

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
            'migrationPath' => $this->getBasePath().'/migrations',
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
        $path = $migrator->migrationPath.'/Install.php';

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
        $path = $this->getBasePath().'/icon-mask.svg';

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
        $plugin = app()->make(static::class, $config);

        foreach ($config as $key => $value) {
            if (property_exists($plugin, $key)) {
                $plugin->{$key} = $value;
            }
        }

        app()->singleton(static::class, fn () => $plugin);

        return $plugin;
    }

    /** {@inheritdoc} */
    public static function getInstance(): PluginInterface
    {
        return app(static::class);
    }
}
