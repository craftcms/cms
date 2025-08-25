<?php

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Concerns\HasCommands;
use CraftCms\Cms\Plugin\Concerns\HasEditions;
use CraftCms\Cms\Plugin\Concerns\HasElementTypes;
use CraftCms\Cms\Plugin\Concerns\HasFieldtypes;
use CraftCms\Cms\Plugin\Concerns\HasListeners;
use CraftCms\Cms\Plugin\Concerns\HasRoutes;
use CraftCms\Cms\Plugin\Concerns\HasSettings;
use CraftCms\Cms\Plugin\Concerns\HasTranslations;
use CraftCms\Cms\Plugin\Concerns\HasUtilities;
use CraftCms\Cms\Plugin\Concerns\HasViews;
use CraftCms\Cms\Plugin\Concerns\HasViteAssets;
use CraftCms\Cms\Plugin\Concerns\HasWidgets;
use CraftCms\Cms\Plugin\Concerns\Installable;
use CraftCms\Cms\Plugin\Concerns\PublishesFiles;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

abstract class Plugin extends ServiceProvider implements PluginInterface
{
    use HasCommands;
    use HasEditions;
    use HasElementTypes;
    use HasFieldtypes;
    use HasListeners;
    use HasRoutes;
    use HasSettings;
    use HasTranslations;
    use HasUtilities;
    use HasViews;
    use HasViteAssets;
    use HasWidgets;
    use Installable;
    use PublishesFiles;

    /** @var string The plugin's handle */
    public string $handle;

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

    /** @var bool Whether the plugin has its own section in the control panel */
    public bool $hasCpSection = false;

    /** @var string The minimum required version the plugin has to be so it can be updated. */
    public string $minVersionRequired = '';

    private ?Plugins $pluginsService = null;

    protected ?string $basePath = null;

    /**
     * @internal
     */
    public function register(): void
    {
        $this->registerTraits();
        $this->registerPlugin();
    }

    /**
     * @internal
     */
    public function boot(Plugins $plugins): void
    {
        $this->pluginsService = $plugins;

        $handle = $this->pluginsService->getPluginHandleByClass(static::class);

        if (! $handle) {
            return;
        }

        if (! $this->pluginsService->isPluginInstalled($handle)) {
            return;
        }

        if (! $this->pluginsService->isPluginEnabled($handle)) {
            return;
        }

        $this->bootTraits();
        $this->bootPlugin();
    }

    protected function registerTraits(): void
    {
        $uses = class_uses_recursive(static::class);

        $conventionalRegisterMethods = array_map(static fn ($trait) => 'register'.class_basename($trait), $uses);

        foreach (new ReflectionClass(static::class)->getMethods() as $method) {
            if (in_array($method->getName(), $conventionalRegisterMethods)) {
                $this->{$method->getName()}();
            }
        }
    }

    protected function bootTraits(): void
    {
        $uses = class_uses_recursive(static::class);

        $conventionalBootMethods = array_map(static fn ($trait) => 'boot'.class_basename($trait), $uses);

        foreach (new ReflectionClass(static::class)->getMethods() as $method) {
            if (in_array($method->getName(), $conventionalBootMethods)) {
                $this->{$method->getName()}();
            }
        }
    }

    public function registerPlugin(): void {}

    public function bootPlugin(): void {}

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
        $plugin = app()->make(static::class, array_merge($config, ['app' => app()]));

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
