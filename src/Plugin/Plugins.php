<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin;

use craft\helpers\FileHelper;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Events\DisablingPlugin;
use CraftCms\Cms\Plugin\Events\EnablingPlugin;
use CraftCms\Cms\Plugin\Events\InstallingPlugin;
use CraftCms\Cms\Plugin\Events\LoadingPlugins;
use CraftCms\Cms\Plugin\Events\PluginDisabled;
use CraftCms\Cms\Plugin\Events\PluginEnabled;
use CraftCms\Cms\Plugin\Events\PluginInstalled;
use CraftCms\Cms\Plugin\Events\PluginRegistered;
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Plugin\Events\PluginsLoaded;
use CraftCms\Cms\Plugin\Events\PluginUninstalled;
use CraftCms\Cms\Plugin\Events\PluginUnregistered;
use CraftCms\Cms\Plugin\Events\SavingPluginSettings;
use CraftCms\Cms\Plugin\Events\UninstallingPlugin;
use CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use Illuminate\Cache\Repository;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use InvalidArgumentException;
use PDOException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

use function CraftCms\Cms\t;

#[Singleton]
class Plugins
{
    /**
     * @var array[] Custom plugin configurations.
     */
    public array $pluginConfigs;

    /**
     * @var bool Whether plugins have been loaded yet for this request
     */
    private bool $pluginsLoaded = false;

    /**
     * @var bool Whether plugins are in the middle of being loaded
     */
    private bool $loadingPlugins = false;

    /**
     * @var ?PluginInterface[] All the enabled plugins, indexed by handles
     */
    private ?array $plugins = null;

    /**
     * @var array Plugin info provided by Composer, indexed by handles
     */
    private array $composerPluginInfo;

    /**
     * @var array All of the stored info for plugins (enabled or disabled), indexed by handles
     *
     * @see getStoredPluginInfo()
     */
    private array $storedPluginInfo;

    /**
     * @var string[]|string|null Any plugin handles that must be disabled per the `disablePlugins` config setting
     */
    private string|array|null $forceDisabledPlugins;

    /**
     * @var string[] Cache for [[getPluginHandleByClass()]]
     */
    private array $classPluginHandles = [];

    private array $viteConfigs = [];

    private array $styles = [];

    private array $scripts = [];

    public function __construct(
        private readonly Repository $cache,
        Application $app,
        Filesystem $files,
        GeneralConfig $generalConfig
    ) {
        if ($generalConfig->safeMode) {
            $this->forceDisabledPlugins = '*';
        } else {
            $this->forceDisabledPlugins = is_array($generalConfig->disabledPlugins) ? array_flip($generalConfig->disabledPlugins) : $generalConfig->disabledPlugins;
        }

        $this->composerPluginInfo = [];

        $path = $app->basePath('vendor/craftcms/plugins.php');

        if (! $files->exists($path)) {
            return;
        }

        /** @var array $plugins */
        $plugins = require $path;

        foreach ($plugins as $packageName => $plugin) {
            $plugin['packageName'] = $packageName;

            // Normalize the base path (and find the actual path, not a possibly-symlinked path)
            if (isset($plugin['basePath'])) {
                if (($basePath = realpath($plugin['basePath'])) !== false) {
                    $plugin['basePath'] = FileHelper::normalizePath($basePath);
                } else {
                    Log::warning("Invalid plugin base path: {$plugin['basePath']}", [__METHOD__]);
                    unset($plugin['basePath']);
                }
            }

            $handle = $this->normalizeHandle(Arr::pull($plugin, 'handle'));
            $this->composerPluginInfo[$handle] = $plugin;
        }
    }

    /**
     * Loads the enabled plugins.
     */
    public function loadPlugins(): void
    {
        if ($this->pluginsLoaded === true || $this->loadingPlugins === true || ! Cms::isInstalled()) {
            return;
        }

        // Prevent this function from getting called twice.
        $this->loadingPlugins = true;

        event(new LoadingPlugins);

        // Find all of the installed plugins
        $this->storedPluginInfo = DB::table(Table::PLUGINS)
            ->orderBy('handle')
            ->get()
            ->keyBy('handle')
            ->map(function (object $row) {
                try {
                    $configData = $this->getPluginConfigData($row->handle);
                } catch (InvalidPluginException) {
                    return false;
                }

                // Clean up the row data
                $row = (array) $row;
                $row['edition'] = $configData['edition'] ?? null;
                $row['settings'] = $configData['settings'] ?? [];
                $row['licenseKey'] = $configData['licenseKey'] ?? null;
                $row['enabled'] = ! empty($configData['enabled']);
                $row['installDate'] = Date::parse($row['installDate']);

                return $row;
            })
            ->filter()
            ->all();

        $anyVersionsChanged = false;

        foreach ($this->storedPluginInfo as $handle => $row) {
            // Skip disabled plugins
            if (! $row['enabled']) {
                continue;
            }

            try {
                $plugin = $this->createPlugin($handle, $row);
            } catch (InvalidPluginException) {
                $plugin = null;
            }

            if ($plugin === null) {
                continue;
            }

            $hasVersionChanged = $this->hasPluginVersionNumberChanged($plugin);

            // If the plugin’s version just changed, make sure the old version is >= the min allowed version
            if (
                $hasVersionChanged &&
                $plugin->minVersionRequired &&
                ! str_starts_with((string) $row['version'], 'dev-') &&
                ! str_ends_with((string) $row['version'], '-dev') &&
                version_compare($row['version'], $plugin->minVersionRequired, '<')
            ) {
                throw new HttpException(200, t(
                    'You need to be on at least {plugin} {version} before you can update to {plugin} {targetVersion}.',
                    [
                        'version' => $plugin->minVersionRequired,
                        'targetVersion' => $plugin->version,
                        'plugin' => $plugin->name,
                    ]));
            }

            // If we're not updating, check if the plugin’s version number changed, but not its schema version.
            if (! app()->isDownForMaintenance() && $hasVersionChanged && ! $this->isPluginUpdatePending($plugin)) {
                // Update our record of the plugin’s version number
                DB::table(Table::PLUGINS)
                    ->where('id', $row['id'])
                    ->update([
                        'version' => $plugin->version,
                        'dateUpdated' => now(),
                    ]);

                $anyVersionsChanged = true;
            }

            $this->registerPlugin($plugin);
        }

        if ($anyVersionsChanged) {
            // Clear the license info cache
            $this->cache->forget(License::CACHE_KEY_LICENSE_INFO);
        }

        // Sort enabled plugins by their names
        $this->plugins = Collection::make($this->plugins)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();

        $this->loadingPlugins = false;
        $this->pluginsLoaded = true;

        event(PluginsLoaded::class);
    }

    /**
     * Returns whether plugins have been loaded yet for this request.
     */
    public function arePluginsLoaded(): bool
    {
        return $this->pluginsLoaded;
    }

    /**
     * Returns an enabled plugin by its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPlugin(string $handle): ?PluginInterface
    {
        $this->loadPlugins();

        return $this->plugins[$handle] ?? null;
    }

    /**
     * Returns an enabled plugin by its package name.
     *
     * @param  string  $packageName  The plugin’s package name
     * @return PluginInterface|null The plugin, or null if it doesn’t exist
     */
    public function getPluginByPackageName(string $packageName): ?PluginInterface
    {
        $this->loadPlugins();

        return Arr::first(
            $this->plugins,
            fn (PluginInterface $plugin) => $plugin->packageName === $packageName,
        );
    }

    /**
     * Returns the plugin handle that contains the given class, if any.
     *
     * The plugin may not actually be installed.
     *
     * @param  class-string  $class
     * @return string|null The plugin handle, or null if it can’t be determined
     */
    public function getPluginHandleByClass(string $class): ?string
    {
        if (array_key_exists($class, $this->classPluginHandles)) {
            return $this->classPluginHandles[$class];
        }

        // Figure out the path to the folder that contains this class
        try {
            // Add a trailing slash so we don't get false positives
            $classPath = Str::finish(FileHelper::normalizePath(dirname(new ReflectionClass($class)->getFileName())), '/');
        } catch (ReflectionException) {
            return $this->classPluginHandles[$class] = null;
        }

        // Find the plugin that contains this path (if any)
        foreach ($this->composerPluginInfo as $handle => $info) {
            if (isset($info['basePath']) && str_starts_with($classPath, Str::finish($info['basePath'], '/'))) {
                return $this->classPluginHandles[$class] = $handle;
            }
        }

        return $this->classPluginHandles[$class] = null;
    }

    /**
     * Returns all the enabled plugins.
     *
     * @return PluginInterface[]
     */
    public function getAllPlugins(): array
    {
        $this->loadPlugins();

        return $this->plugins ?? [];
    }

    /**
     * Enables a plugin by its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @return bool Whether the plugin was enabled successfully
     *
     * @throws InvalidPluginException if the plugin isn't installed
     */
    public function enablePlugin(string $handle): bool
    {
        if ($this->isPluginEnabled($handle)) {
            return true;
        }

        if (($info = $this->getStoredPluginInfo($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        if (($plugin = $this->createPlugin($handle, $info)) === null) {
            throw new InvalidPluginException($handle);
        }

        event(new EnablingPlugin($plugin));

        // Enable the plugin in the project config
        app(ProjectConfig::class)->set(
            path: ProjectConfig::PATH_PLUGINS.'.'.$handle.'.enabled',
            value: true,
            message: "Enable plugin “{$handle}”"
        );

        $this->storedPluginInfo[$handle]['enabled'] = true;
        $this->registerPlugin($plugin);

        event(new PluginEnabled($plugin));

        return true;
    }

    /**
     * Disables a plugin by its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @return bool Whether the plugin was disabled successfully
     *
     * @throws InvalidPluginException if the plugin isn’t installed
     */
    public function disablePlugin(string $handle): bool
    {
        if (! $this->isPluginInstalled($handle)) {
            throw new InvalidPluginException($handle);
        }

        if (! $this->isPluginEnabled($handle)) {
            return true;
        }

        if (($plugin = $this->getPlugin($handle)) === null) {
            throw new InvalidPluginException($handle);
        }

        event(new DisablingPlugin($plugin));

        // Disable the plugin in the project config
        app(ProjectConfig::class)->set(
            ProjectConfig::PATH_PLUGINS.'.'.$handle.'.enabled',
            false,
            "Disable plugin “{$handle}”"
        );

        $this->storedPluginInfo[$handle]['enabled'] = false;
        $this->unregisterPlugin($plugin);

        event(new PluginDisabled($plugin));

        return true;
    }

    /**
     * Installs a plugin by its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @param  string|null  $edition  The plugin’s edition
     * @return bool Whether the plugin was installed successfully.
     *
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function installPlugin(string $handle, ?string $edition = null): bool
    {
        $this->loadPlugins();

        if ($this->getStoredPluginInfo($handle) !== null) {
            return true;
        }

        // Temporarily allow changes to the project config even if it's supposed to be read only
        $projectConfig = app(ProjectConfig::class);
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        $configKey = ProjectConfig::PATH_PLUGINS.'.'.$handle;

        $plugin = $this->createPlugin($handle);

        if ($plugin === null) {
            throw new InvalidPluginException($handle);
        }

        // Set the edition
        if ($edition === null) {
            // See if one is already set in the project config
            $edition = $projectConfig->get($configKey.'.edition');
        }

        $editions = $plugin::editions();

        if ($edition === null || ! in_array($edition, $editions, true)) {
            $edition = reset($editions);
        }

        $plugin->edition = $edition;

        event(new InstallingPlugin($plugin));

        DB::beginTransaction();

        try {
            // Make sure the plugin doesn't have a row in the `plugins` or `migrations` tables first, just in case
            DB::table(Table::PLUGINS)->where('handle', $handle)->delete();

            DB::table(Table::MIGRATIONS)
                ->where('track', "plugin:$handle")
                ->delete();

            $info['id'] = DB::table(Table::PLUGINS)->insertGetId([
                'handle' => $handle,
                'version' => $plugin->version,
                'schemaVersion' => $plugin->schemaVersion,
                'installDate' => $now = now(),
                'dateCreated' => $now,
                'dateUpdated' => $now,
                'uid' => Str::uuid(),
            ]);

            $info['enabled'] = $projectConfig->get($configKey.'.enabled') ?? true;

            $plugin->install();

            try {
                DB::commit();
            } catch (PDOException $e) {
                // The transaction could be implicitly committed by Mysql
                if ($e->getMessage() !== 'There is no active transaction') {
                    throw $e;
                }
            }
        } catch (Throwable $e) {
            try {
                DB::rollBack();
            } catch (PDOException $e) {
                // Implicitly committed.
            }

            if (DB::isMysql()) {
                // Explicitly remove the plugins row just in case the transaction was implicitly committed
                DB::table(Table::PLUGINS)->where('handle', $handle)->delete();
            }

            throw $e;
        }

        // Add the plugin to the project config
        $projectConfig->set(
            path: $configKey,
            value: [
                'edition' => $edition,
                'enabled' => true,
                'schemaVersion' => $plugin->schemaVersion,
            ],
            message: "Install plugin “{$handle}”",
        );

        $this->storedPluginInfo[$handle] = $info;
        $this->registerPlugin($plugin);

        event(new PluginInstalled($plugin));

        $projectConfig->readOnly = $readOnly;

        return true;
    }

    /**
     * Uninstalls a plugin by its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @param  bool  $force  Whether to force the plugin uninstallation, even if it is disabled, its
     *                       `uninstall()` method returns `false`, or its files aren’t present
     * @return bool Whether the plugin was uninstalled successfully
     *
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws Throwable if reasons
     */
    public function uninstallPlugin(string $handle, bool $force = false): bool
    {
        $this->loadPlugins();

        if (! $this->isPluginInstalled($handle)) {
            return true;
        }

        $enabled = $this->isPluginEnabled($handle);

        if (! $enabled && ! $force) {
            // Don't allow uninstalling disabled plugins, because that could be buggy
            // if the plugin was composer-updated while disabled, and its uninstall()
            // function is out of sync with what's actually in the database
            throw new InvalidPluginException($handle, 'Uninstalling disabled plugins is not allowed.');
        }

        // Temporarily allow changes to the project config even if it's supposed to be read only
        $projectConfig = app(ProjectConfig::class);
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        if (($plugin = $this->getPlugin($handle)) === null && ! $force) {
            throw new InvalidPluginException($handle);
        }

        event(new UninstallingPlugin($plugin));

        DB::beginTransaction();
        try {
            // Let the plugin uninstall itself first
            if ($plugin && $enabled) {
                try {
                    $plugin->uninstall();
                } catch (Throwable $e) {
                    if (! $force) {
                        throw $e;
                    }
                }
            }

            // Clean up the plugins and migrations tables
            $info = $this->getStoredPluginInfo($handle);
            if ($info !== null) {
                DB::table(Table::PLUGINS)->delete($info['id']);
            }

            DB::table(Table::MIGRATIONS)
                ->where('track', "plugin:$handle")
                ->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Remove the plugin from the project config
        if ($projectConfig->get(ProjectConfig::PATH_PLUGINS.'.'.$handle, true)) {
            $projectConfig->remove(ProjectConfig::PATH_PLUGINS.'.'.$handle, "Uninstall the “{$handle}” plugin");
        }

        if ($plugin) {
            $this->unregisterPlugin($plugin);
        }

        unset($this->storedPluginInfo[$handle]);

        event(new PluginUninstalled($plugin));

        $projectConfig->readOnly = $readOnly;

        return true;
    }

    /**
     * Switches a plugin’s edition.
     *
     * @param  string  $handle  The plugin’s handle
     * @param  string  $edition  The plugin’s edition
     *
     * @throws InvalidPluginException if the plugin doesn’t exist
     * @throws InvalidArgumentException if $edition is invalid
     * @throws Throwable if reasons
     */
    public function switchEdition(string $handle, string $edition): void
    {
        $info = $this->getPluginInfo($handle);

        /** @var class-string<PluginInterface> $class */
        $class = $info['class'];

        if (! in_array($edition, $class::editions(), true)) {
            throw new InvalidArgumentException('Invalid plugin edition: '.$edition);
        }

        // Update the project config
        app(ProjectConfig::class)->set(
            path: ProjectConfig::PATH_PLUGINS.'.'.$handle.'.edition',
            value: $edition,
            message: "Switch edition for plugin “{$handle}”",
        );

        if (isset($this->storedPluginInfo[$handle])) {
            $this->storedPluginInfo[$handle]['edition'] = $edition;
        }

        // If it's installed, update the instance and our locally stored info
        $plugin = $this->getPlugin($handle);

        if ($plugin !== null) {
            $plugin->edition = $edition;
        }

    }

    /**
     * Saves a plugin’s settings.
     *
     * @param  PluginInterface  $plugin  The plugin
     * @param  array  $settings  The plugin’s new settings
     * @return bool Whether the plugin’s settings were saved successfully
     */
    public function savePluginSettings(PluginInterface $plugin, array $settings): bool
    {
        if (is_null($pluginSettings = $plugin->getSettings())) {
            return false;
        }

        // Save the settings on the plugin
        $pluginSettings->setAttributes($settings);

        // Validate them, now that it's a model
        if ($pluginSettings->validate() === false) {
            return false;
        }

        event($event = new SavingPluginSettings($plugin));

        if (! $event->isValid) {
            return false;
        }

        if (! $plugin->beforeSaveSettings()) {
            return false;
        }

        // Update the plugin’s settings in the project config
        $pluginSettings = ProjectConfigHelper::packAssociativeArrays($pluginSettings->getAttributes());
        app(ProjectConfig::class)->set(
            path: ProjectConfig::PATH_PLUGINS.'.'.$plugin->handle.'.settings',
            value: $pluginSettings,
            message: "Change settings for plugin “{$plugin->handle}”",
        );

        $plugin->afterSaveSettings();

        event(new PluginSettingsSaved($plugin));

        return true;
    }

    /**
     * Returns whether the given plugin’s version number has changed from what we have recorded in the database.
     *
     * @param  PluginInterface  $plugin  The plugin
     * @return bool Whether the plugin’s version number has changed from what we have recorded in the database
     */
    public function hasPluginVersionNumberChanged(PluginInterface $plugin): bool
    {
        $this->loadPlugins();

        if (($info = $this->getStoredPluginInfo($plugin->handle)) === null) {
            return false;
        }

        return $plugin->version !== $info['version'];
    }

    /**
     * Returns whether the given plugin’s local schema version is greater than the record we have in the database.
     *
     * @param  PluginInterface  $plugin  The plugin
     * @return bool Whether the plugin’s local schema version is greater than the record we have in the database
     */
    public function isPluginUpdatePending(PluginInterface $plugin): bool
    {
        $this->loadPlugins();

        if (($info = $this->getStoredPluginInfo($plugin->handle)) === null) {
            return false;
        }

        return version_compare($plugin->schemaVersion, $info['schemaVersion'], '>');
    }

    /**
     * Returns whether a given plugin is installed (even if it's disabled).
     *
     * @param  string  $handle  The plugin handle
     */
    public function isPluginInstalled(string $handle): bool
    {
        $this->loadPlugins();

        return isset($this->storedPluginInfo[$handle]);
    }

    /**
     * Returns whether a given plugin is installed and enabled.
     *
     * @param  string  $handle  The plugin handle
     */
    public function isPluginEnabled(string $handle): bool
    {
        $this->loadPlugins();

        return $this->storedPluginInfo[$handle]['enabled'] ?? false;
    }

    /**
     * Returns whether a given plugin is installed but disabled.
     *
     * @param  string  $handle  The plugin handle
     */
    public function isPluginDisabled(string $handle): bool
    {
        return ! $this->isPluginEnabled($handle) && $this->isPluginInstalled($handle);
    }

    /**
     * Returns the stored info for a given plugin.
     *
     * @param  string  $handle  The plugin handle
     * @return array|null The stored info, if there is any
     */
    public function getStoredPluginInfo(string $handle): ?array
    {
        $this->loadPlugins();

        return $this->storedPluginInfo[$handle] ?? null;
    }

    /**
     * Updates a plugin’s stored version & schema version to match what’s Composer-installed.
     */
    public function updatePluginVersionInfo(PluginInterface $plugin): void
    {
        DB::table(Table::PLUGINS)
            ->where('handle', $plugin->handle)
            ->update([
                'version' => $plugin->version,
                'schemaVersion' => $plugin->schemaVersion,
                'dateUpdated' => now(),
            ]);

        // Update our cache of the versions
        $this->loadPlugins();
        if (isset($this->storedPluginInfo[$plugin->handle])) {
            $this->storedPluginInfo[$plugin->handle]['version'] = $plugin->version;
            $this->storedPluginInfo[$plugin->handle]['schemaVersion'] = $plugin->schemaVersion;
        }

        app(ProjectConfig::class)->set(
            path: sprintf('%s.%s.schemaVersion', ProjectConfig::PATH_PLUGINS, $plugin->handle),
            value: $plugin->schemaVersion,
            message: "Update plugin schema version for “{$plugin->handle}”",
        );

        // Clear the license info cache
        $this->cache->forget(License::CACHE_KEY_LICENSE_INFO);
    }

    /**
     * Returns the Composer-supplied info
     *
     * @param  string|null  $handle  The plugin handle. If null is passed, info for all Composer-installed plugins will be returned.
     * @return array|null The plugin info, or null if an unknown handle was passed.
     */
    public function getComposerPluginInfo(?string $handle = null): ?array
    {
        if ($handle === null) {
            return $this->composerPluginInfo;
        }

        return $this->composerPluginInfo[$handle] ?? null;
    }

    /**
     * Creates and returns a new plugin instance based on its handle.
     *
     * @param  string  $handle  The plugin’s handle
     * @param  array|null  $info  The plugin’s stored info, if any
     *
     * @throws InvalidPluginException if $handle is invalid
     */
    public function createPlugin(string $handle, ?array $info = null): ?PluginInterface
    {
        if (! isset($this->composerPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        $config = $this->composerPluginInfo[$handle];

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $path) {
                Aliases::set($alias, $path);
            }

            unset($config['aliases']);
        }

        /** @var class-string<PluginInterface>|class-string<object> $class */
        $class = $config['class'];

        // Make sure the class exists and it implements PluginInterface
        if (! is_subclass_of($class, PluginInterface::class)) {
            return null;
        }

        // Is it installed?
        if ($info !== null) {
            $config['isInstalled'] = true;

            // Set the edition
            $config['edition'] = $info['edition'] ?? 'standard';
            $editions = $class::editions();
            if (! in_array($config['edition'], $editions, true)) {
                $config['edition'] = Arr::first($editions);
            }

            $settings = array_merge(
                $info['settings'] ?? [],
                Config::get("craft.$handle", []),
            );

            if ($settings !== []) {
                $config['settings'] = $settings;
            }

            // Merge in the custom config, if there is one
            if (isset($this->pluginConfigs[$handle])) {
                $config = Arr::merge($config, $this->pluginConfigs[$handle]);
            }
        }

        $config['handle'] = $handle;

        return $class::create($config);
    }

    /**
     * Returns info about all of the plugins we can find, whether they’re installed or not.
     */
    public function getAllPluginInfo(): Collection
    {
        $this->loadPlugins();

        return Collection::make($this->composerPluginInfo)
            ->keys()
            ->mapWithKeys(fn (string $handle) => [$handle => $this->getPluginInfo($handle)])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * Returns info about a plugin, whether it's installed or not.
     *
     * @param  string  $handle  The plugin’s handle
     *
     * @throws InvalidPluginException if the plugin isn't Composer-installed
     */
    public function getPluginInfo(string $handle): array
    {
        if (! isset($this->composerPluginInfo[$handle])) {
            throw new InvalidPluginException($handle);
        }

        $pluginInfo = $this->getStoredPluginInfo($handle);

        // Get the plugin if it's enabled
        $plugin = $this->getPlugin($handle);

        $info = array_merge([
            'developer' => null,
            'developerUrl' => null,
            'description' => null,
            'documentationUrl' => null,
        ], $this->composerPluginInfo[$handle]);

        $edition = $pluginInfo['edition'] ?? 'standard';
        if ($plugin) {
            $editions = $plugin::editions();
            if (! in_array($edition, $editions, true)) {
                $edition = reset($editions);
            }
        } else {
            $editions = ['standard'];
        }

        $info['isInstalled'] = $installed = $pluginInfo !== null;
        $info['isEnabled'] = $plugin !== null;
        $info['private'] = str_starts_with($handle, '_');
        $info['moduleId'] = $handle;
        $info['edition'] = $edition;
        $info['hasMultipleEditions'] = count($editions) > 1;
        $info['hasCpSettings'] = $plugin->hasCpSettings ?? false;
        $info['hasReadOnlyCpSettings'] = $plugin->hasReadOnlyCpSettings ?? false;
        $info['licenseKey'] = $pluginInfo['licenseKey'] ?? null;

        $licenseInfo = $this->cache->get(License::CACHE_KEY_LICENSE_INFO, []);
        $pluginCacheKey = Str::start($handle, 'plugin-');
        $info['licenseId'] = $licenseInfo[$pluginCacheKey]['id'] ?? null;
        $info['licensedEdition'] = $licenseInfo[$pluginCacheKey]['edition'] ?? null;
        $info['licenseKeyStatus'] = $licenseInfo[$pluginCacheKey]['status'] ?? LicenseKeyStatus::Unknown->value;
        $info['licenseIssues'] = $installed ? $this->getLicenseIssues($handle) : [];

        $info['isTrial'] = (
            $installed &&
            (
                $info['licenseKeyStatus'] === LicenseKeyStatus::Trial->value ||
                (
                    $info['licenseKeyStatus'] === LicenseKeyStatus::Valid->value &&
                    ! empty($pluginInfo['licensedEdition'])
                    && $pluginInfo['licensedEdition'] !== $edition
                )
            )
        );

        // An upgrade is available if the plugin is in trial or licensed to less than the best edition
        $info['upgradeAvailable'] = (
            $info['isTrial'] ||
            (
                $info['hasMultipleEditions'] &&
                (
                    (! empty($pluginInfo['licensedEdition']) && $pluginInfo['licensedEdition'] !== end($editions)) ||
                    ($pluginInfo['edition'] ?? 'standard') !== end($editions)
                )
            )
        );

        return $info;
    }

    /**
     * Returns whether a plugin has licensing issues.
     */
    public function hasIssues(string $handle): bool
    {
        return ! empty($this->getLicenseIssues($handle));
    }

    /**
     * Returns any issues with a plugin license.
     *
     * The response will be an array containing a combination of these strings:
     *
     * - `wrong_edition` – if the current edition isn't the licensed one, and
     *   testing editions isn't allowed
     * - `mismatched` – if the license key is tied to a different Craft license
     * - `astray` – if the installed version is greater than the highest version
     *   the license is allowed to run
     * - `required` – if no license key is present but one is required
     * - `invalid` – if a license key is present but it’s invalid
     *
     *
     * @return string[]
     */
    public function getLicenseIssues(string $handle): array
    {
        $pluginInfo = $this->getStoredPluginInfo($handle);

        if ($pluginInfo === null) {
            return [];
        }

        $status = $pluginInfo['licenseKeyStatus'] ?? LicenseKeyStatus::Unknown->value;

        if ($status === LicenseKeyStatus::Unknown->value) {
            // Either we don't know yet, or the plugin is free
            return [];
        }

        $issues = [];

        // Make sure they're allowed to run the current edition
        $canTestEditions = Edition::canTest();
        if (
            ! $canTestEditions &&
            isset($pluginInfo['edition'], $pluginInfo['licensedEdition']) &&
            $pluginInfo['edition'] !== $pluginInfo['licensedEdition']
        ) {
            $issues[] = 'wrong_edition';
        }

        // General license issues
        switch ($pluginInfo['licenseKeyStatus']) {
            case LicenseKeyStatus::Trial->value:
                if (! $canTestEditions) {
                    $issues[] = empty($pluginInfo['licenseKey']) ? 'required' : 'no_trials';
                }
                break;
            case LicenseKeyStatus::Invalid->value:
            case LicenseKeyStatus::Mismatched->value:
            case LicenseKeyStatus::Astray->value:
                $issues[] = $pluginInfo['licenseKeyStatus'];
                break;
        }

        return $issues;
    }

    /**
     * Returns a plugin’s SVG icon.
     *
     * @param  string  $handle  The plugin’s handle
     * @return string The given plugin’s SVG icon
     */
    public function getPluginIconSvg(string $handle): string
    {
        // If it's installed, let the plugin say where it lives
        if (($plugin = $this->getPlugin($handle)) !== null) {
            $basePath = $plugin->getBasePath();
        } else {
            if (($basePath = $this->composerPluginInfo[$handle]['basePath'] ?? false) !== false) {
                $basePath = Aliases::get($basePath);
            }
        }

        $iconPath = ($basePath !== false) ? $basePath.'/icon.svg' : false;

        if ($iconPath === false || ! is_file($iconPath) || ! FileHelper::isSvg($iconPath)) {
            $iconPath = Aliases::get('@appicons/default-plugin.svg');
        }

        return file_get_contents($iconPath);
    }

    /**
     * Returns the license key stored for a given plugin, if it was purchased through the Store.
     *
     * @param  string  $handle  The plugin’s handle
     * @return string|null The plugin’s license key, or null if it isn’t known
     *
     * @throws InvalidLicenseKeyException
     */
    public function getPluginLicenseKey(string $handle): ?string
    {
        $licenseKey = Env::parse($this->getStoredPluginInfo($handle)['licenseKey'] ?? null);

        // also check if pc has the license key
        if ($licenseKey === null) {
            $pcPlugins = app(ProjectConfig::class)->get(ProjectConfig::PATH_PLUGINS);
            $licenseKey = Env::parse($pcPlugins[$handle]['licenseKey'] ?? null);
        }

        return $this->normalizePluginLicenseKey($licenseKey);
    }

    /**
     * Sets a plugin’s license key.
     *
     * Note this should *not* be used to store license keys generated by third party stores.
     *
     * @param  string  $handle  The plugin’s handle
     * @param  string|null  $licenseKey  The plugin’s license key
     * @return bool Whether the license key was updated successfully
     *
     * @throws InvalidPluginException if the plugin isn't installed
     * @throws InvalidLicenseKeyException if $licenseKey is invalid
     */
    public function setPluginLicenseKey(string $handle, ?string $licenseKey = null): bool
    {
        // Validate the license key
        $normalizedLicenseKey = $this->normalizePluginLicenseKey($licenseKey);

        // If the license key is set to an empty environment variable, set the environment variable's value
        $oldLicenseKey = $this->getStoredPluginInfo($handle)['licenseKey'] ?? null;
        // https://github.com/craftcms/cms/issues/12687 - check if the .env file exists first
        if (
            preg_match('/^\$(\w+)$/', (string) $oldLicenseKey, $matches) &&
            in_array(Env::get($matches[1]), ['', null], true) &&
            file_exists(app()->environmentFilePath())
        ) {
            Env::writeVariable($matches[1], $normalizedLicenseKey, app()->environmentFilePath());
        } else {
            // Set the plugin's license key in the project config
            app(ProjectConfig::class)->set(
                path: sprintf('%s.%s.licenseKey', ProjectConfig::PATH_PLUGINS, $handle),
                value: $normalizedLicenseKey,
                message: "Set license key for plugin “{$handle}”",
            );

            // Update our cache of it
            $this->loadPlugins();
            if (isset($this->storedPluginInfo[$handle])) {
                $this->storedPluginInfo[$handle]['licenseKey'] = $normalizedLicenseKey;
            }
        }

        // Clear the license info cache
        $this->cache->forget(License::CACHE_KEY_LICENSE_INFO);

        return true;
    }

    /**
     * Normalizes a plugin license key.
     *
     *
     * @throws InvalidLicenseKeyException
     */
    public function normalizePluginLicenseKey(?string $licenseKey = null): ?string
    {
        if (empty($licenseKey)) {
            return null;
        }

        if (str_starts_with($licenseKey, '$')) {
            return $licenseKey;
        }

        // Normalize to just uppercase numbers/letters
        $licenseKey = mb_strtoupper($licenseKey);
        $licenseKey = preg_replace('/[^A-Z0-9]/', '', $licenseKey);

        if (strlen((string) $licenseKey) !== 24) {
            // Invalid key
            throw new InvalidLicenseKeyException($licenseKey);
        }

        return $licenseKey;
    }

    /**
     * Returns the license key status of a given plugin.
     *
     * @param  string  $handle  The plugin’s handle
     */
    public function getPluginLicenseKeyStatus(string $handle): LicenseKeyStatus
    {
        $info = $this->getStoredPluginInfo($handle);

        return LicenseKeyStatus::tryFrom($info['licenseKeyStatus'] ?? '') ?? LicenseKeyStatus::Unknown;
    }

    public function addViteConfig(string $handle, array $config): void
    {
        $this->viteConfigs[$handle] = $config;
    }

    public function addStyle(string $handle, string $path): void
    {
        $this->styles[$handle] ??= [];
        $this->styles[$handle][] = $path;
    }

    public function addScript(string $handle, string $path): void
    {
        $this->scripts[$handle] ??= [];
        $this->scripts[$handle][] = $path;
    }

    public function getAssetsHtml(): string
    {
        $html = '';

        foreach ($this->viteConfigs as $vite) {
            $html .= Vite::useHotFile($vite['hotFile'])
                ->useBuildDirectory(Str::chopEnd($vite['buildDirectory'], '/'))
                ->withEntryPoints($vite['input'])
                ->toHtml();
        }

        foreach ($this->styles as $styles) {
            foreach ($styles as $style) {
                $html .= '<link rel="stylesheet" href="'.$style.'">';
            }
        }

        foreach ($this->scripts as $scripts) {
            foreach ($scripts as $script) {
                $html .= '<script src="'.$script.'" defer></script>';
            }
        }

        return $html;
    }

    /**
     * Converts old school camelCase handles to kebab-case.
     */
    private function normalizeHandle(string $handle): string
    {
        if (strtolower($handle) !== $handle) {
            return preg_replace('/\-{2,}/', '-', str($handle)->slug()->value());
        }

        return $handle;
    }

    /**
     * Registers a plugin internally and as an application module.
     *
     * This should only be called for enabled plugins
     *
     * @param  PluginInterface  $plugin  The plugin
     */
    private function registerPlugin(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->handle] = $plugin;

        event(new PluginRegistered($plugin));
    }

    /**
     * Unregisters a plugin internally and as an application module.
     *
     * @param  PluginInterface  $plugin  The plugin
     */
    private function unregisterPlugin(PluginInterface $plugin): void
    {
        unset($this->plugins[$plugin->handle]);

        event(new PluginUnregistered($plugin));
    }

    /**
     * Load config data for plugin by its handle.
     *
     *
     * @throws InvalidPluginException if plugin not found
     */
    private function getPluginConfigData(string $handle): array
    {
        $projectConfig = app(ProjectConfig::class);
        $configKey = ProjectConfig::PATH_PLUGINS.'.'.$handle;
        $data = $projectConfig->get($configKey);

        if (! empty($data['settings'])) {
            $data['settings'] = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
        }

        if (! $data) {
            throw new InvalidPluginException($handle);
        }

        // Force disable it?
        if (
            $this->forceDisabledPlugins === '*' ||
            (is_array($this->forceDisabledPlugins) && isset($this->forceDisabledPlugins[$handle]))
        ) {
            $data['enabled'] = false;
        }

        return $data;
    }
}
