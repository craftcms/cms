<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use Composer\InstalledVersions;
use Craft;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\TerminalCommands;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Mail\LegacyTransportCompatibility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpToken;
use ReflectionClass;
use ReflectionProperty;
use Throwable;
use yii\base\Component;
use yii\base\Event;

use function CraftCms\Cms\t;

/**
 * Runs the Yii2 Loose Ends utility’s checks, which look at the project as it is right now.
 *
 * @internal
 */
class LooseEndsChecks
{
    public const string ADAPTER_PACKAGE = 'craftcms/yii2-adapter';

    /**
     * Legacy environment variables the adapter falls back on, and the variables that replace them.
     */
    private const array LEGACY_ENV_VARS = [
        'CRAFT_DB_TABLE_PREFIX' => 'DB_PREFIX',
        'DB_TABLE_PREFIX' => 'DB_PREFIX',
        'CRAFT_ENVIRONMENT' => 'APP_ENV',
        'ENVIRONMENT' => 'APP_ENV',
        'CRAFT_DB_DRIVER' => 'DB_CONNECTION',
    ];

    public function __construct(
        private readonly CodeOwnership $ownership,
    ) {
    }

    /**
     * @return list<Finding>
     */
    public function run(): array
    {
        return [
            ...$this->configFiles(),
            ...$this->generalConfigSettings(),
            ...$this->legacyEnvVars(),
            ...$this->mailTransport(),
            ...$this->usageStorage(),
            ...$this->projectLayout(),
            ...$this->database(),
            ...$this->content(),
            ...$this->yiiModules(),
            ...$this->yiiEventHandlers(),
            ...$this->packages(),
        ];
    }

    /**
     * @return list<Finding>
     */
    public function configFiles(): array
    {
        $findings = [];
        $configPath = config_path('craft');

        if (!is_dir($configPath)) {
            return [];
        }

        foreach (File::glob("$configPath/*.php") as $file) {
            $name = basename($file);
            $config = $this->inspectConfigFile($file);

            if ($config['legacyNames'] !== []) {
                $findings[] = $this->finding(
                    "config.legacy-classes:$name",
                    Section::Config,
                    Severity::Breaks,
                    t('`config/craft/{file}` uses classes that the Yii2 adapter provides.', ['file' => $name], 'yii2-adapter'),
                    t('Switch to their Craft 6 equivalents, such as `CraftCms\\Cms\\Config\\GeneralConfig` for `craft\\config\\GeneralConfig`, and `CraftCms\\Cms\\Support\\Env::get()` for `craft\\helpers\\App::env()`.', category: 'yii2-adapter'),
                    detail: collect($config['legacyNames'])->map(fn(string $name) => "`$name`")->join(', '),
                    file: $file,
                );
            }

            if (in_array('*', $config['keys'] ?? [], true)) {
                $findings[] = $this->finding(
                    "config.multi-environment:$name",
                    Section::Config,
                    Severity::Breaks,
                    t('`config/craft/{file}` is a multi-environment config file.', ['file' => $name], 'yii2-adapter'),
                    t('Remove the `*` and environment keys, and set values that vary by environment with environment variables.', category: 'yii2-adapter'),
                    file: $file,
                );
            }
        }

        $general = "$configPath/general.php";

        if (is_file($general)) {
            $config = $this->inspectConfigFile($general);

            if ($config['callable']) {
                $findings[] = $this->finding(
                    'config.general-callable',
                    Section::Config,
                    Severity::Breaks,
                    t('`config/craft/general.php` returns a function.', category: 'yii2-adapter'),
                    t('Return an array or a `GeneralConfig::create()` chain instead.', category: 'yii2-adapter'),
                    file: $general,
                );
            }

            if ($config['keys'] !== null) {
                $renamed = array_values(array_intersect($config['keys'], array_keys($this->renamedSettings())));

                if ($renamed !== []) {
                    $findings[] = $this->finding(
                        'config.renamed-settings',
                        Section::Config,
                        Severity::Breaks,
                        t('`config/craft/general.php` uses renamed settings.', category: 'yii2-adapter'),
                        t('Use the new setting names.', category: 'yii2-adapter'),
                        detail: collect($renamed)
                            ->map(fn(string $old) => "$old → {$this->renamedSettings()[$old]}")
                            ->join(', '),
                        file: $general,
                    );
                }
            }
        }

        foreach (['web', 'console'] as $type) {
            $file = "$configPath/general.$type.php";

            if (is_file($file)) {
                $findings[] = $this->finding(
                    "config.general-$type",
                    Section::Config,
                    Severity::Breaks,
                    t('`config/craft/general.{type}.php` overrides general settings for {type} requests.', ['type' => $type], 'yii2-adapter'),
                    t('Merge its settings into `general.php`, and check `app()->runningInConsole()` for any that should differ.', category: 'yii2-adapter'),
                    file: $file,
                );
            }
        }

        $anchors = "$configPath/anchors.php";

        if (is_file($anchors) && !$this->inspectConfigFile($anchors)['empty']) {
            $findings[] = $this->finding(
                'config.anchors',
                Section::Config,
                Severity::ChangesBehavior,
                t('`config/craft/anchors.php` configures anchors.', category: 'yii2-adapter'),
                t('Move its settings to the `anchors` setting in `general.php`.', category: 'yii2-adapter'),
                file: $anchors,
            );
        }

        $routes = "$configPath/routes.php";

        if (is_file($routes) && !$this->inspectConfigFile($routes)['empty']) {
            $findings[] = $this->finding(
                'config.routes',
                Section::Config,
                Severity::Breaks,
                t('`config/craft/routes.php` defines site routes.', category: 'yii2-adapter'),
                t('Define them as Laravel routes in `routes/web.php` instead.', category: 'yii2-adapter'),
                file: $routes,
            );
        }

        foreach (File::glob("$configPath/htmlpurifier/*.json") as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $findings[] = $this->finding(
                "config.htmlpurifier:$name",
                Section::Config,
                Severity::Breaks,
                t('`config/craft/htmlpurifier/{file}` is an HTML Purifier config.', ['file' => basename($file)], 'yii2-adapter'),
                t('Register a “{name}” sanitizer in `config/craft/sanitizers/` instead.', ['name' => $name], 'yii2-adapter'),
                file: $file,
            );
        }

        $appConfig = config('craft.app');

        if (is_array($appConfig) && $appConfig !== []) {
            $findings[] = $this->finding(
                'config.app',
                Section::Code,
                Severity::Breaks,
                t('`config/craft/app.php` configures the Yii application.', category: 'yii2-adapter'),
                t('Move its modules, components, and bootstrap classes to Laravel service providers.', category: 'yii2-adapter'),
                detail: implode(', ', array_keys($appConfig)),
                file: is_file("$configPath/app.php") ? "$configPath/app.php" : null,
            );
        }

        if (function_exists('craft_modify_app_config')) {
            $function = new \ReflectionFunction('craft_modify_app_config');
            $findings[] = $this->finding(
                'config.modify-app-config',
                Section::Code,
                Severity::Breaks,
                t('The `craft_modify_app_config()` function modifies the Yii application config.', category: 'yii2-adapter'),
                t('Move what it does to a Laravel service provider.', category: 'yii2-adapter'),
                file: $function->getFileName() ?: null,
                line: $function->getStartLine() ?: null,
            );
        }

        return $findings;
    }

    /**
     * Finds general config settings that are set, which only the adapter supports.
     *
     * @return list<Finding>
     */
    public function generalConfigSettings(): array
    {
        $config = Cms::config();

        if (!$config instanceof LegacyGeneralConfig) {
            return [];
        }

        $settings = $config->getDeprecatedSettings();

        if ($settings === []) {
            return [];
        }

        ksort($settings);


        return [
            $this->finding(
                'config.removed-settings',
                Section::Config,
                Severity::Breaks,
                t('General config settings that Craft no longer supports are set.', category: 'yii2-adapter'),
                t('Remove them from `config/craft/general.php` and the environment, and configure what each one did as described.', category: 'yii2-adapter'),
                detail: collect($settings)->map(fn(string $guidance, string $name) => "`$name`: $guidance")->join("\n"),
            ),
        ];
    }

    /**
     * @return list<Finding>
     */
    public function legacyEnvVars(): array
    {
        $vars = [];

        foreach (self::LEGACY_ENV_VARS as $legacy => $replacement) {
            $value = Env::get($legacy);

            if ($replacement === 'DB_PREFIX') {
                // Like the adapter, which applies a legacy prefix whenever `DB_PREFIX` is empty, with an underscore.
                if ($value && !Env::get('DB_PREFIX')) {
                    $vars[$legacy] = sprintf('DB_PREFIX=%s', Str::finish((string)$value, '_'));
                }
            } elseif ($value !== null && Env::get($replacement) === null) {
                $vars[$legacy] = $replacement;
            }
        }

        if ($vars === []) {
            return [];
        }

        return [
            $this->finding(
                'env.legacy-vars',
                Section::Config,
                Severity::Breaks,
                t('Craft 5 environment variables are set.', category: 'yii2-adapter'),
                t('Rename each one to its Laravel equivalent. Craft 5 added an underscore to the end of the table prefix, but `DB_PREFIX` is used as is, so include it there.', category: 'yii2-adapter'),
                detail: collect($vars)->map(fn(string $new, string $old) => "`$old` → `$new`")->join(', '),
            ),
        ];
    }

    /**
     * Finds a Craft 5 mail transport adapter that email is still sent through.
     *
     * @return list<Finding>
     */
    public function mailTransport(): array
    {
        $type = app(ProjectConfig::class)->get('email.transportType');

        if (!is_string($type) || $type === '' || Config::get('mail.default') !== LegacyTransportCompatibility::MAILER) {
            return [];
        }

        return [
            $this->finding(
                'config.mail-transport',
                Section::Config,
                Severity::Breaks,
                t('Email is sent through the “{name}” transport adapter saved in the project config.', [
                    'name' => is_subclass_of($type, TransportAdapterInterface::class) ? $type::displayName() : $type,
                ], 'yii2-adapter'),
                t('Choose a mailer in Settings → Email, or set `MAIL_MAILER`.', category: 'yii2-adapter'),
            ),
        ];
    }

    /**
     * Reports a failure to save usage, as code paths that ran meanwhile aren’t listed.
     *
     * @return list<Finding>
     */
    public function usageStorage(): array
    {
        $error = Cache::get(LooseEndsStore::WRITE_ERROR_CACHE_KEY);

        if (!is_string($error)) {
            return [];
        }

        return [
            $this->finding(
                'usage.write-failed',
                Section::Config,
                Severity::Breaks,
                t('Usage of the Yii2 adapter couldn’t be saved.', category: 'yii2-adapter'),
                t('Make sure the database user can create the `{table}` table and write to it. Code that ran while saving failed isn’t listed, so this stays until usage is saved again.', ['table' => LooseEndsStore::TABLE], 'yii2-adapter'),
                detail: $error,
            ),
        ];
    }

    /**
     * @return list<Finding>
     */
    public function projectLayout(): array
    {
        $findings = [];

        if (is_dir(base_path('translations'))) {
            $findings[] = $this->finding(
                'layout.translations',
                Section::Config,
                Severity::Breaks,
                t('Site translations are in the `translations/` folder.', category: 'yii2-adapter'),
                t('Rename the folder to `lang/`.', category: 'yii2-adapter'),
                file: base_path('translations'),
            );
        }

        if (is_dir(resource_path('views')) && is_dir(base_path('templates')) && File::allFiles(base_path('templates')) !== []) {
            $findings[] = $this->finding(
                'layout.templates',
                Section::Config,
                Severity::Breaks,
                t('Site templates are in both `templates/` and `resources/views/`.', category: 'yii2-adapter'),
                t('Move the templates in `templates/` to `resources/views/`.', category: 'yii2-adapter'),
                file: base_path('templates'),
            );
        }

        if (Edition::isAtLeast(Edition::Pro)) {
            $rebrand = app(Rebrand::class);

            foreach (['icon' => 'cpIconUrl', 'logo' => 'cpLogoUrl'] as $type => $setting) {
                $image = $type === 'icon' ? $rebrand->getIcon() : $rebrand->getLogo();

                if ($image !== null && Cms::config()->$setting === $image->getUrl()) {
                    $findings[] = $this->finding(
                        "layout.rebrand-$type",
                        Section::Config,
                        Severity::Cosmetic,
                        $type === 'icon'
                            ? t('The control panel icon was uploaded in the control panel.', category: 'yii2-adapter')
                            : t('The control panel logo was uploaded in the control panel.', category: 'yii2-adapter'),
                        t('Set the `{setting}` general config setting instead.', ['setting' => $setting], 'yii2-adapter'),
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * @return list<Finding>
     */
    public function database(): array
    {
        $findings = [];

        try {
            if (Schema::hasTable(Table::SESSIONS) && !Schema::hasColumn(Table::SESSIONS, 'payload')) {
                $findings[] = $this->finding(
                    'database.sessions',
                    Section::Content,
                    Severity::Breaks,
                    t('The `sessions` table has the Craft 5 schema.', category: 'yii2-adapter'),
                    t('Run `{command}`.', ['command' => app(TerminalCommands::class)->craft('migrate:sessions-table')], 'yii2-adapter'),
                );
            }
        } catch (Throwable) {
        }

        $filesystems = app(ProjectConfig::class)->get('fs');

        if (is_array($filesystems) && $filesystems !== []) {
            $findings[] = $this->finding(
                'database.filesystems',
                Section::Content,
                Severity::Breaks,
                t('Craft filesystems are defined in project config.', category: 'yii2-adapter'),
                t('Define them as disks in `config/filesystems.php`, and point volumes at those disks.', category: 'yii2-adapter'),
                detail: implode(', ', array_keys($filesystems)),
            );
        }

        $bridgeDisks = collect(config('filesystems.disks', []))
            ->filter(fn(mixed $disk) => is_array($disk) && ($disk['driver'] ?? null) === LegacyFsFlysystemAdapter::DISK_DRIVER)
            ->keys();

        if ($bridgeDisks->isNotEmpty()) {
            $findings[] = $this->finding(
                'database.filesystem-bridge',
                Section::Content,
                Severity::Breaks,
                t('Disks use the `{driver}` driver.', ['driver' => LegacyFsFlysystemAdapter::DISK_DRIVER], 'yii2-adapter'),
                t('Give them a native Laravel disk driver.', category: 'yii2-adapter'),
                detail: $bridgeDisks->join(', '),
            );
        }

        $legacyMigrations = $this->pendingYiiMigrations();

        if ($legacyMigrations !== []) {
            $findings[] = $this->finding(
                'database.yii-migrations',
                Section::Content,
                Severity::Breaks,
                t('Content migrations that haven’t run yet extend `craft\db\Migration`.', category: 'yii2-adapter'),
                t('Rewrite them to extend `CraftCms\Cms\Database\Migration`.', category: 'yii2-adapter'),
                detail: implode(', ', $legacyMigrations),
            );
        }


        return $findings;
    }

    /**
     * Counts categories, tags, and global sets, and the fields that relate to them.
     *
     * @return list<Finding>
     */
    public function content(): array
    {
        $findings = [];
        $terminal = app(TerminalCommands::class);
        $concepts = [
            'categories' => [
                DeprecatedConcepts::supportsCategories(),
                DeprecatedTable::CATEGORIES,
                t('Categories', category: 'yii2-adapter'),
                $terminal->craft('entrify:categories'),
                $terminal->craft('drop-categories-support'),
            ],
            'tags' => [
                DeprecatedConcepts::supportsTags(),
                DeprecatedTable::TAGS,
                t('Tags', category: 'yii2-adapter'),
                $terminal->craft('entrify:tags'),
                $terminal->craft('drop-tags-support'),
            ],
            'global-sets' => [
                DeprecatedConcepts::supportsGlobalSets(),
                DeprecatedTable::GLOBALSETS,
                t('Global sets', category: 'yii2-adapter'),
                $terminal->craft('entrify:global-set'),
                $terminal->craft('drop-global-sets-support'),
            ],
        ];

        foreach ($concepts as $key => [$supported, $table, $label, $entrify, $drop]) {
            if (!$supported) {
                continue;
            }

            $count = $this->countLiveElements($table);

            $findings[] = $count > 0
                ? $this->finding(
                    "content.$key",
                    Section::Content,
                    Severity::Breaks,
                    t('{label} still have content.', ['label' => $label], 'yii2-adapter'),
                    t('Convert them to entries with `{entrify}`, then run `{drop}`.', ['entrify' => $entrify, 'drop' => $drop], 'yii2-adapter'),
                    detail: t('{count, number} to convert', ['count' => $count], 'yii2-adapter'),
                )
                : $this->finding(
                    "content.$key",
                    Section::Content,
                    Severity::Breaks,
                    t('{label} support is still turned on, but nothing uses it.', ['label' => $label], 'yii2-adapter'),
                    t('Run `{drop}` to drop the empty tables.', ['drop' => $drop], 'yii2-adapter'),
                );
        }

        try {
            $fields = DB::table(Table::FIELDS)
                ->whereIn('type', ['craft\fields\Categories', 'craft\fields\Tags'])
                ->whereNull('dateDeleted')
                ->pluck('handle')
                ->all();
        } catch (Throwable) {
            $fields = [];
        }

        if ($fields !== []) {
            $findings[] = $this->finding(
                'content.relation-fields',
                Section::Content,
                Severity::Breaks,
                t('Categories and Tags fields are in use.', category: 'yii2-adapter'),
                t('Converting categories and tags to entries also converts these fields to Entries fields.', category: 'yii2-adapter'),
                detail: implode(', ', $fields),
            );
        }

        return $findings;
    }

    /**
     * Finds Yii modules that belong to the project.
     *
     * @return list<Finding>
     */
    public function yiiModules(): array
    {
        if (!Craft::$app) {
            return [];
        }

        $findings = [];

        foreach (Craft::$app->getModules() as $id => $module) {
            $class = is_object($module) ? $module::class : ($module['class'] ?? (is_string($module) ? $module : null));

            if (!is_string($class) || !class_exists($class)) {
                continue;
            }

            $file = new ReflectionClass($class)->getFileName();

            if (!is_string($file) || !$this->ownership->isProjectFile($file)) {
                continue;
            }

            $findings[] = $this->finding(
                "code.module:$id",
                Section::Code,
                Severity::Breaks,
                t('The “{id}” Yii module is part of the project.', ['id' => $id], 'yii2-adapter'),
                t('Convert it to a Laravel service provider.', category: 'yii2-adapter'),
                file: $file,
            );
        }

        return $findings;
    }

    /**
     * Finds Yii event handlers registered by project code.
     *
     * @return list<Finding>
     */
    public function yiiEventHandlers(): array
    {
        $handlers = [];

        foreach (['_events', '_eventWildcards'] as $property) {
            $events = new ReflectionProperty(Event::class, $property)->getValue();

            foreach ($events as $name => $classes) {
                foreach ($classes as $class => $classHandlers) {
                    foreach ($classHandlers as [$handler]) {
                        $handlers[] = [$class, $name, $handler];
                    }
                }
            }
        }

        if (Craft::$app) {
            $components = ['app' => Craft::$app, ...Craft::$app->getComponents(false)];
            $property = new ReflectionProperty(Component::class, '_events');

            foreach ($components as $id => $component) {
                if (!$component instanceof Component) {
                    continue;
                }

                foreach ($property->getValue($component) as $name => $componentHandlers) {
                    foreach ($componentHandlers as [$handler]) {
                        $handlers[] = [$id === 'app' ? 'Craft::$app' : "Craft::\$app->$id", $name, $handler];
                    }
                }
            }
        }

        $findings = [];

        foreach ($handlers as [$target, $name, $handler]) {
            $location = $this->ownership->callableLocation($handler);

            if ($location === null || !$this->ownership->isProjectFile($location[0])) {
                continue;
            }

            [$file, $line] = $location;
            $findings[] = $this->finding(
                'code.yii-event:' . sha1("$target|$name|$file|$line"),
                Section::Code,
                Severity::Breaks,
                t('A Yii event handler listens for `{target}::{event}`.', ['target' => $target, 'event' => $name], 'yii2-adapter'),
                t('Listen for the equivalent Laravel event instead.', category: 'yii2-adapter'),
                file: $file,
                line: $line,
            );
        }

        return $findings;
    }

    /**
     * Finds installed packages that require or suggest the adapter.
     *
     * @return list<Finding>
     */
    public function packages(): array
    {
        $packages = $this->adapterDependents();

        if ($packages === []) {
            return [];
        }

        $plugins = $this->pluginInfoByPackage();
        $updates = $this->craftnetInfo($packages);
        $packageIcon = str_replace('<svg ', '<svg fill="currentColor" ', (string)file_get_contents(CmsAssets::resourcesPath('icons/light/box.svg')));
        $findings = [];

        foreach ($packages as $name => $version) {
            $plugin = $plugins[$name] ?? null;
            $info = $plugin !== null ? ($updates[$plugin['handle']] ?? null) : null;
            $abandoned = (bool)($info['abandoned'] ?? false);
            $release = ($info['status'] ?? null) === 'update-available'
                ? ((($info['installed']['known'] ?? true) ? ($info['earliest'] ?? null) : null) ?? $info['latest'] ?? null)
                : null;
            $status = match (true) {
                $release !== null => PackageStatus::UpdateAvailable,
                ($info['status'] ?? null) === 'no-release' => $abandoned ? PackageStatus::Abandoned : PackageStatus::NoRelease,
                default => PackageStatus::Unknown,
            };

            $fix = match (true) {
                $release !== null && ($release['compatible'] ?? false) => t('Update to {version} or later, which no longer needs the adapter.', ['version' => $release['version']], 'yii2-adapter'),
                $release !== null => t('Update to {version} or later, which no longer needs the adapter. It requires Craft {constraint}.', [
                    'version' => $release['version'],
                    'constraint' => $release['cmsConstraint'] ?? '?',
                ], 'yii2-adapter'),
                $status === PackageStatus::Abandoned && isset($info['replacement']['name']) => t('It’s no longer maintained. Replace it with {replacement}.', ['replacement' => $info['replacement']['name']], 'yii2-adapter'),
                $status === PackageStatus::Abandoned => t('It’s no longer maintained. Replace it, or remove it.', category: 'yii2-adapter'),
                $status === PackageStatus::NoRelease => t('No release that drops the adapter is available yet. Check with its developer.', category: 'yii2-adapter'),
                default => t('No update information is available. Check with its developer.', category: 'yii2-adapter'),
            };

            $findings[] = new Finding(
                "package:$name",
                $plugin !== null ? Section::Plugins : Section::Packages,
                Severity::Breaks,
                t('{package} {version}', ['package' => $plugin['name'] ?? $name, 'version' => $version], 'yii2-adapter'),
                $fix,
                detail: $release !== null && ($release['majorUpdate'] ?? false)
                    ? t('This is a major update, so check its upgrade notes first.', category: 'yii2-adapter')
                    : null,
                url: $info['changelogUrl'] ?? $info['url'] ?? null,
                urlLabel: isset($info['changelogUrl'])
                    ? t('Changelog', category: 'yii2-adapter')
                    : (isset($info['url']) ? t('Plugin Store', category: 'yii2-adapter') : null),
                iconSvg: $plugin['iconSvg'] ?? $packageIcon,
                status: $status,
                update: $plugin !== null && $release !== null && ($release['compatible'] ?? false)
                    ? ['handle' => $plugin['handle'], 'packageName' => $name, 'version' => $release['version']]
                    : null,
            );
        }

        return $findings;
    }

    /**
     * Returns installed packages whose composer.json requires or suggests the adapter, with their versions.
     *
     * @return array<string, string>
     */
    public function adapterDependents(): array
    {
        $installedJson = dirname(new ReflectionClass(InstalledVersions::class)->getFileName()) . '/installed.json';

        if (!is_file($installedJson)) {
            return [];
        }

        try {
            $installed = Json::decodeFromFile($installedJson);
        } catch (Throwable) {
            return [];
        }

        $packages = [];

        foreach ($installed['packages'] ?? $installed as $package) {
            $name = $package['name'] ?? null;

            if (!is_string($name) || strtolower($name) === self::ADAPTER_PACKAGE) {
                continue;
            }

            $links = array_change_key_case([...($package['require'] ?? []), ...($package['suggest'] ?? [])]);

            if (isset($links[self::ADAPTER_PACKAGE])) {
                $packages[$name] = (string)($package['pretty_version'] ?? $package['version'] ?? '');
            }
        }

        ksort($packages);

        return $packages;
    }

    /**
     * Returns the installed plugins’ handles, names, and icons, keyed by package name.
     *
     * @return array<string, array{handle: string, name: string, iconSvg: string}>
     */
    private function pluginInfoByPackage(): array
    {
        try {
            return app(Plugins::class)->getAllPluginInfo()
                ->mapWithKeys(fn(array $info, string $handle) => [$info['packageName'] => [
                    'handle' => $handle,
                    'name' => $info['name'],
                    'iconSvg' => $info['iconSvg'],
                ]])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Asks Craftnet which updates drop the adapter, for the plugins that need it.
     *
     * @param array<string, string> $packages
     * @return array<string, array<string, mixed>>
     */
    private function craftnetInfo(array $packages): array
    {
        $key = 'yii2-loose-ends:craftnet:' . sha1(Cms::VERSION . '|' . Json::encode($packages));

        try {
            return Cache::remember($key, 3600, function() {
                $plugins = app(Api::class)->request('GET', 'yii2-adapter-info')->json('plugins');

                return is_array($plugins) ? $plugins : [];
            });
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function pendingYiiMigrations(): array
    {
        $path = database_path('migrations');

        if (!is_dir($path)) {
            return [];
        }

        try {
            $ran = DB::table(Table::MIGRATIONS)->whereNull('track')->pluck('migration')->all();
        } catch (Throwable) {
            $ran = [];
        }

        $pending = [];

        foreach (File::glob("$path/*.php") as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($name, $ran, true)) {
                continue;
            }

            $contents = (string)file_get_contents($file);

            if (preg_match('/\bcraft\\\\db\\\\Migration\b/', $contents)) {
                $pending[] = $name;
            }
        }

        return $pending;
    }

    private function countLiveElements(string $table): int
    {
        try {
            return DB::table($table)
                ->join(Table::ELEMENTS, Table::ELEMENTS . '.id', '=', "$table.id")
                ->whereNull(Table::ELEMENTS . '.dateDeleted')
                ->whereNull(Table::ELEMENTS . '.draftId')
                ->whereNull(Table::ELEMENTS . '.revisionId')
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, string>
     */
    private function renamedSettings(): array
    {
        return new ReflectionProperty(LegacyGeneralConfig::class, 'renamedSettings')->getValue();
    }



    /**
     * Reads what a config file returns from its source, without running it.
     *
     * Running a config file again after boot would apply its settings a second time, over the environment overrides
     * already applied to them, and run any other code in it.
     *
     * @return array{
     *     callable: bool,
     *     keys: list<string>|null,
     *     empty: bool,
     *     legacyNames: list<string>,
     * } `keys` holds the returned array’s top-level string keys, or null if it doesn’t return an array literal.
     * `legacyNames` holds the classes from the `craft\` namespace, and the `Craft` class, that the file references.
     */
    private function inspectConfigFile(string $file): array
    {
        $result = ['callable' => false, 'keys' => null, 'empty' => false, 'legacyNames' => []];

        try {
            $tokens = array_values(array_filter(
                PhpToken::tokenize((string)file_get_contents($file)),
                fn(PhpToken $token) => !$token->isIgnorable(),
            ));
        } catch (Throwable) {
            return $result;
        }

        $legacyNames = [];
        $braceDepth = 0;
        $return = null;

        foreach ($tokens as $i => $token) {
            if ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                $name = ltrim($token->text, '\\');

                if (str_starts_with(strtolower($name), 'craft\\')) {
                    $legacyNames[$name] = true;
                } elseif (strcasecmp($name, 'Craft') === 0 && ($tokens[$i + 1] ?? null)?->is(T_DOUBLE_COLON)) {
                    $legacyNames['Craft'] = true;
                }
            } elseif ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $braceDepth++;
            } elseif ($token->text === '}') {
                $braceDepth--;
            } elseif ($token->is(T_RETURN) && $braceDepth === 0) {
                $return = $i;
            }
        }

        $result['legacyNames'] = array_keys($legacyNames);

        if ($return === null) {
            return $result;
        }

        $i = $return + 1;

        if (($tokens[$i] ?? null)?->is(T_STATIC)) {
            $i++;
        }

        if (($tokens[$i] ?? null)?->is([T_FUNCTION, T_FN])) {
            $result['callable'] = true;

            return $result;
        }

        if (($tokens[$i] ?? null)?->is(T_ARRAY) && ($tokens[$i + 1] ?? null)?->text === '(') {
            $i++;
        } elseif (($tokens[$i] ?? null)?->text !== '[') {
            return $result;
        }

        $keys = [];
        $depth = 0;
        $empty = true;

        for (; $i < count($tokens); $i++) {
            $text = $tokens[$i]->text;

            if ($depth > 0) {
                $empty = $empty && in_array($text, [']', ')'], true) && $depth === 1;
            }

            if (in_array($text, ['[', '(', '{'], true) || $tokens[$i]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif (in_array($text, [']', ')', '}'], true)) {
                $depth--;

                if ($depth === 0) {
                    break;
                }
            } elseif (
                $depth === 1 &&
                $tokens[$i]->is(T_CONSTANT_ENCAPSED_STRING) &&
                ($tokens[$i + 1] ?? null)?->is(T_DOUBLE_ARROW)
            ) {
                $keys[] = stripcslashes(substr($text, 1, -1));
            }
        }

        $result['keys'] = $keys;
        $result['empty'] = $empty;

        return $result;
    }

    private function finding(
        string $key,
        Section $section,
        Severity $severity,
        string $title,
        string $fix,
        ?string $detail = null,
        ?string $file = null,
        ?int $line = null,
        ?string $url = null,
        ?string $urlLabel = null,
    ): Finding {
        return new Finding(
            $key,
            $section,
            $severity,
            $title,
            $fix,
            $detail,
            $file !== null ? $this->ownership->relativePath($file) : null,
            $line,
            $url,
            $urlLabel,
        );
    }
}
