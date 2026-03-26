<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Support\File;
use Illuminate\Support\ServiceProvider;
use Override;
use ReflectionClass;

abstract class Plugin extends ServiceProvider implements PluginInterface
{
    use Concerns\HasCommands;
    use Concerns\HasConfig;
    use Concerns\HasEditions;
    use Concerns\HasElementTypes;
    use Concerns\HasFieldtypes;
    use Concerns\HasFrontendAssets;
    use Concerns\HasListeners;
    use Concerns\HasPermissions;
    use Concerns\HasRoutes;
    use Concerns\HasSettings;
    use Concerns\HasTranslations;
    use Concerns\HasUtilities;
    use Concerns\HasViews;
    use Concerns\HasWidgets;
    use Concerns\Installable;
    use Concerns\InteractsWithCp;
    use Concerns\PublishesFiles;

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

    /** @var string The minimum required version the plugin has to be so it can be updated. */
    public string $minVersionRequired = '';

    protected ?Plugins $pluginsService = null;

    /**
     * @internal
     */
    #[Override]
    public function register(): void
    {
        $this->setupTraits('register');
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

        $this->setupTraits('boot');
        $this->bootPlugin();
    }

    private function setupTraits(string $method): void
    {
        $usesRecursive = once(fn () => class_uses_recursive(static::class));

        collect($usesRecursive)
            ->map(fn (string $trait) => class_basename($trait))
            ->each(function (string $trait) use ($method) {
                if (! method_exists($this, $method = "{$method}{$trait}")) {
                    return;
                }

                $this->$method();
            });
    }

    public function registerPlugin(): void {}

    public function bootPlugin(): void {}

    #[Override]
    public function getBasePath(): string
    {
        return once(function () {
            $class = new ReflectionClass($this);

            return dirname($class->getFileName());
        });
    }

    public function getMigrationsPath(): string
    {
        $conventionalPath = dirname($this->getBasePath()).'/database/migrations';

        if (File::isDirectory($conventionalPath)) {
            return $conventionalPath;
        }

        $fallbackPath = $this->getBasePath().'/migrations';

        if (File::isDirectory($fallbackPath)) {
            return $fallbackPath;
        }

        return $conventionalPath;
    }

    #[Override]
    public static function create(array $config): PluginInterface
    {
        $plugin = app()->make(static::class, array_merge($config, ['app' => app()]));

        foreach ($config as $key => $value) {
            if ($key === 'settings') {
                $model = $plugin->createSettingsModel();
                $model?->setAttributes($value);
                $plugin->settings = $model;

                continue;
            }

            if (property_exists($plugin, $key)) {
                $plugin->{$key} = $value;
            }
        }

        app()->singleton(static::class, fn () => $plugin);

        return $plugin;
    }

    /**
     * @return static
     */
    #[Override]
    public static function getInstance(): PluginInterface
    {
        return app(static::class);
    }
}
