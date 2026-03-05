<?php

namespace CraftCms\Yii2Adapter;

use Craft;
use craft\base\BaseFsInterface;
use craft\base\Event as YiiEvent;
use craft\base\FieldLayoutComponent;
use craft\console\controllers\HelpController;
use craft\controllers\UsersController;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\events\DefineGqlArgumentsEvent;
use craft\events\EditionChangeEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\events\RegisterGqlEagerLoadableFields;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fields\Categories as CategoriesField;
use craft\fields\linktypes\Category as CategoryLinkType;
use craft\fields\Tags as TagsField;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\gql\ArgumentManager;
use craft\gql\base\ElementArguments;
use craft\gql\ElementQueryConditionBuilder;
use craft\gql\handlers\RelatedCategories;
use craft\gql\handlers\RelatedTags;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\mutations\Category as CategoryMutation;
use craft\gql\mutations\GlobalSet as GlobalSetMutation;
use craft\gql\mutations\Tag as TagMutation;
use craft\gql\queries\Category as CategoryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\Tag as TagQuery;
use craft\gql\types\input\criteria\CategoryRelation;
use craft\gql\types\input\criteria\TagRelation;
use craft\models\CategoryGroup;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\services\Addresses;
use craft\services\Auth;
use craft\services\Dashboard;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Fs;
use craft\services\Gc;
use craft\services\Gql;
use craft\services\Plugins as LegacyPlugins;
use craft\services\ProjectConfig as LegacyProjectConfig;
use craft\services\Revisions;
use craft\services\Routes;
use craft\services\Search as LegacySearch;
use craft\services\Sites;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use craft\web\Application;
use craft\web\twig\Extension;
use craft\web\twig\GlobalsExtension;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\Cp as CpVariable;
use craft\web\UrlManager;
use craft\web\View;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Data\FolderCriteria as AssetFolderCriteria;
use CraftCms\Cms\Asset\Data\Volume as AssetVolume;
use CraftCms\Cms\Asset\Data\VolumeFolder as AssetVolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Cp\Events\RegisterCpNavItems;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition\Events\EditionChanged;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Jobs\PropagateElements;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Events\FieldCachesInvalidated;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\Events\RegisterLinkTypes;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing as FilesystemFsListing;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem as FilesystemComponent;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use CraftCms\Cms\ProjectConfig\Events\RebuildConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\DependencyAwareCache\Events\TagsInvalidated;
use CraftCms\Yii2Adapter\Console\AddCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\AddGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\AddTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\DropGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use CraftCms\Yii2Adapter\Console\MigrateMigrationTableCommand;
use CraftCms\Yii2Adapter\Console\MigrateSessionsTableCommand;
use CraftCms\Yii2Adapter\Console\RepairCategoryGroupStructureCommand;
use CraftCms\Yii2Adapter\Http\Controller;
use CraftCms\Yii2Adapter\Mixins\ElementMixin;
use CraftCms\Yii2Adapter\Mixins\ElementQueryMixin;
use CraftCms\Yii2Adapter\Mixins\UserMixin;
use CraftCms\Yii2Adapter\Mixins\ValidateMixin;
use CraftCms\Yii2Adapter\Mixins\VolumeMixin;
use GraphQL\Type\Definition\Type;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use PDOException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Throwable;
use yii\BaseYii;
use yii\caching\TagDependency as YiiTagDependency;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use function CraftCms\Cms\t;

class Yii2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMultiEnvironmentConfigs();
        $this->registerConstants();
        $this->registerMacros();
        $this->registerLegacyApp();
        $this->registerFilesystemBridgeDriver();

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->setLaravelDefaults();
    }

    private function registerFilesystemBridgeDriver(): void
    {
        $this->app->make(FilesystemManager::class)->extend(LegacyFsFlysystemAdapter::DISK_DRIVER, function($app, array $config) {
            $handle = $config['fsHandle'] ?? null;
            if (!is_string($handle) || $handle === '') {
                throw new InvalidArgumentException('Missing `fsHandle` configuration for craft-fs-bridge disk.');
            }

            $filesystem = Filesystems::getFilesystemByHandle($handle);
            if (!$filesystem instanceof FsInterface) {
                throw new InvalidArgumentException("Craft filesystem [$handle] is not registered.");
            }

            try {
                $diskConfig = $filesystem->getDiskConfig();
                if (
                    ($diskConfig['driver'] ?? null) === LegacyFsFlysystemAdapter::DISK_DRIVER &&
                    ($diskConfig['fsHandle'] ?? null) === $handle
                ) {
                    if (!$filesystem instanceof BaseFsInterface) {
                        throw new InvalidArgumentException(
                            "Filesystem [$handle] does not provide a usable Laravel disk configuration.",
                        );
                    }

                    return $this->legacyFilesystemAdapter($filesystem, array_merge($config, $diskConfig));
                }

                $disk = $app->make(FilesystemManager::class)->build($diskConfig);

                if (!$disk instanceof LaravelFilesystemAdapter) {
                    throw new InvalidArgumentException("Filesystem [$handle] returned an invalid disk configuration.");
                }

                return $this->filesystemWithPrefix($disk, $config);
            } catch (Throwable $e) {
                if (!$filesystem instanceof BaseFsInterface) {
                    throw new InvalidArgumentException(
                        "Filesystem [$handle] does not provide a usable Laravel disk configuration.",
                        previous: $e,
                    );
                }

                Deprecator::log(
                    sprintf('filesystem-bridge-fallback:%s', $filesystem::class),
                    sprintf(
                        'Filesystem [%s] is using a legacy operation fallback. Implement `%s::getDiskConfig()` so it can be used as a native Laravel disk.',
                        $handle,
                        $filesystem::class,
                    ),
                );

                return $this->legacyFilesystemAdapter($filesystem, $config);
            }
        });
    }

    private function filesystemWithPrefix(LaravelFilesystemAdapter $disk, array $config): LaravelFilesystemAdapter
    {
        $prefix = $config['prefix'] ?? null;
        if (!is_string($prefix) || $prefix === '') {
            return $disk;
        }

        $flysystemAdapter = new PathPrefixedAdapter($disk->getAdapter(), $prefix);

        return new LaravelFilesystemAdapter(
            new Flysystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ])),
            $flysystemAdapter,
            array_merge($disk->getConfig(), $config),
        );
    }

    private function legacyFilesystemAdapter(BaseFsInterface $filesystem, array $config): LaravelFilesystemAdapter
    {
        $adapter = new LegacyFsFlysystemAdapter($filesystem);
        $flysystemAdapter = !empty($config['prefix'])
            ? new PathPrefixedAdapter($adapter, $config['prefix'])
            : $adapter;

        return new LaravelFilesystemAdapter(
            new Flysystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ])),
            $flysystemAdapter,
            $config,
        );
    }

    protected function registerMultiEnvironmentConfigs(): void
    {
        if (!is_dir(config_path('craft'))) {
            return;
        }

        $files = new Finder()->files()->in(config_path('craft'))->name('*.php');
        $environment = $this->app->environment();

        foreach ($files as $file) {
            $key = "craft.{$file->getFilenameWithoutExtension()}";
            $config = Config::get($key);

            if ($config instanceof BaseConfig) {
                continue;
            }

            if (!is_array($config)) {
                Config::set($key, []);

                continue;
            }

            if (!array_key_exists('*', $config)) {
                continue;
            }

            Deprecator::log("config-{$file}", 'Using multi-environment config files is deprecated.', $file->getPathname());

            $merged = Arr::merge($config['*'], $config[$environment] ?? []);

            Config::set($key, $merged);
        }
    }

    protected function registerConstants(): void
    {
        /*
         * This is to prevent Yii from running exit(), we want to catch Yii
         * exiting when for example a redirect is executed.
         */
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        /**
         * Set some base CRAFT variables to their Laravel equivalents.
         */
        defined('YII_DEBUG') || define('YII_DEBUG', config('app.debug'));

        defined('CRAFT_CONFIG_PATH') || define('CRAFT_CONFIG_PATH', config_path('craft'));
        defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', lang_path());
        defined('CRAFT_LICENSE_KEY_PATH') || define('CRAFT_LICENSE_KEY_PATH', config_path('craft/license.key'));
        defined('CRAFT_STORAGE_PATH') || define('CRAFT_STORAGE_PATH', storage_path());
        defined('CRAFT_DOTENV_PATH') || define('CRAFT_DOTENV_PATH', app()->environmentPath());
        defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', base_path('vendor'));

        if (is_dir(resource_path('views'))) {
            defined('CRAFT_TEMPLATES_PATH') || define('CRAFT_TEMPLATES_PATH', resource_path('views'));
        } else {
            defined('CRAFT_TEMPLATES_PATH') || define('CRAFT_TEMPLATES_PATH', base_path('templates'));
        }
    }

    private function registerMacros(): void
    {
        Field::macro('trigger', function($name, mixed $event = null): void {
            Deprecator::log('Field-trigger', 'Calling ->trigger on a Field is deprecated. Switch to component events instead.');

            $event ??= new YiiEvent();

            YiiEvent::trigger($this, $name, $event);

            $this->dispatchComponentEvent($name, $event);
        });

        Element::mixin(new ValidateMixin());
        Element::mixin(new ElementMixin());
        Field::mixin(new ValidateMixin());
        FieldLayoutComponent::mixin(new ValidateMixin());
        FilesystemComponent::mixin(new ValidateMixin());
        ElementQuery::mixin(new ElementQueryMixin());
        User::mixin(new UserMixin());
        AssetFolderCriteria::mixin(new ValidateMixin());
        AssetVolume::mixin(new ValidateMixin());
        AssetVolume::mixin(new VolumeMixin());
        AssetVolumeFolder::mixin(new ValidateMixin());
        FilesystemFsListing::mixin(new ValidateMixin());
        Widget::mixin(new ValidateMixin());
        \CraftCms\Cms\Image\Data\ImageTransform::mixin(new ValidateMixin());
        \CraftCms\Cms\Image\Data\ImageTransformIndex::mixin(new ValidateMixin());
    }

    protected function registerLegacyApp(): void
    {
        $this->app->singleton('Craft', function() {
            /**
             * Register the base aliases that Yii sets, this has to be after
             * the constants as composer will autoload the BaseYii class.
             */
            Aliases::set('@app', base_path());

            foreach (BaseYii::$aliases as $alias => $path) {
                Aliases::set($alias, $path);
            }

            if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
                $app = require __DIR__ . '/../bootstrap/console.php';
            } else {
                /**
                 * Yii seems weird about these
                 */
                $_SERVER = array_merge($_SERVER, [
                    'SCRIPT_FILENAME' => $this->app->publicPath('index.php'),
                    'SCRIPT_NAME' => '/index.php',
                ]);

                $app = require __DIR__ . '/../bootstrap/web.php';

                if (!$app->controller) {
                    $controller = new Controller('', $app);

                    $app->controller = $controller;
                }
            }

            /** @var \craft\web\Application|\craft\console\Application $app */
            $app->setTimeZone(app()->getTimezone());
            $app->language = app()->getLocale();

            Craft::$app = $app;
            Craft::populateCustomFieldBehavior();

            $this->bootEvents();
            self::bootYiiEvents();

            return $app;
        });
    }

    /**
     * Set some compatible Laravel defaults if the environment variables aren't set.
     */
    protected function setLaravelDefaults(): void
    {
        if (!file_exists(config_path('app.php'))) {
            Config::set('app.debug', Env::get('APP_DEBUG', Env::get('CRAFT_DEV_MODE', false)));
            Config::set('app.env', Env::get('APP_ENV', Env::get('CRAFT_ENVIRONMENT', Env::get('ENVIRONMENT', 'local'))));
        }

        if (!file_exists(config_path('session.php'))) {
            Config::set('session.driver', Env::get('SESSION_DRIVER', 'file'));
        }

        if (!file_exists(config_path('cache.php'))) {
            Config::set('cache.default', Env::get('CACHE_STORE', 'file'));
        }

        if (!file_exists(config_path('database.php'))) {
            Config::set('database.default', Env::get('DB_CONNECTION', Env::get('CRAFT_DB_DRIVER', 'mysql')));
        }
    }

    public function boot(): void
    {
        $this->commands([
            AddCategoriesSupportCommand::class,
            AddGlobalSetsSupportCommand::class,
            AddTagsSupportCommand::class,
            DropCategoriesSupportCommand::class,
            DropGlobalSetsSupportCommand::class,
            DropTagsSupportCommand::class,
            MigrateMigrationTableCommand::class,
            MigrateSessionsTableCommand::class,
            RepairCategoryGroupStructureCommand::class,
        ]);

        /**
         * Prefix is not generally a configuration variable that
         * is set through the environment in Laravel, so
         * we set it here for backwards compatibility.
         */
        $connection = Config::get('database.default');
        Config::set("database.connections.{$connection}.prefix", Env::get('DB_TABLE_PREFIX'));

        /**
         * Add fallback for when translations are still stored in `/translations`
         */
        if (is_dir(base_path('translations'))) {
            Deprecator::log('translations-path', 'Storing site translations in `/translations` is deprecated. Rename the folder to `lang` instead.');

            I18N::addCategorySources(new CategorySource(
                'site',
                new MessageSource(base_path('translations')),
                new IntlMessageFormatter(),
            ));
        }

        /**
         * Load legacy translations
         */
        I18N::addCategorySources(new CategorySource(
            'yii2-adapter',
            new MessageSource(dirname(__DIR__) . '/resources/translations'),
            new IntlMessageFormatter(),
        ));

        /**
         * Load legacy Craft
         */
        app('Craft');

        /**
         * Keep legacy CustomFieldBehavior statics in sync when field caches are invalidated.
         */
        Event::listen(FieldCachesInvalidated::class, fn() => Craft::populateCustomFieldBehavior());

        $this->app->booted(function() {
            $this->ensureNewMigrationTable();
            $this->ensureNewSessionsTable();
        });

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->bootLegacyCommands();
    }

    private function bootLegacyCommands(): void
    {
        /**
         * Don't need these for Laravel tests.
         */
        if (app()->environment('testing')) {
            return;
        }

        /** @var \craft\console\Application $app */
        $app = app('Craft');

        $controller = new HelpController('help', $app);
        $commands = $controller->allCommandsInfo();

        foreach ($commands as $command) {
            if (str_contains($command['description'], '. ')) {
                $command['description'] = Str::before($command['description'], '. ') . '. ';
            }

            $signature = str_replace('/', ':', $command['name']);

            foreach ($command['definition']['arguments'] as $definition) {
                $signature .= $this->convertDefinition($definition, 'argument');
            }

            foreach ($command['definition']['options'] as $definition) {
                if ($definition['name'] === '--quiet') {
                    continue;
                }

                $signature .= $this->convertDefinition($definition, 'option');
            }

            ConsoleApplication::starting(function(ConsoleApplication $artisan) use ($app, $command, $signature) {
                $artisanName = explode(' ', $signature)[0];

                if ($artisanName === 'help') {
                    return;
                }

                if ($artisan->has("craft:{$artisanName}")) {
                    return;
                }

                if ($artisan->has($artisanName)) {
                    return;
                }

                $artisan->resolve(new LegacyCraftCommand(
                    app: $app,
                    signature: "craft:{$signature}",
                    description: $command['description'],
                    hidden: str_ends_with($artisanName, ':index'),
                ));

                // Add with slash for backwards compatibility
                $signatureWithSlash = Str::replaceFirst(':', '/', $signature);
                $nameWithSlash = Str::replaceFirst(':', '/', $artisanName);
                $artisan->resolve(new LegacyCraftCommand(
                    app: $app,
                    signature: "craft:{$signatureWithSlash}",
                    description: $command['description'],
                    hidden: true,
                    deprecationMessage: "Calling `php craft $nameWithSlash` is deprecated use `php craft $artisanName` instead.",
                ));
            });
        }
    }

    public function convertDefinition(array $definition, string $type): string
    {
        if ($definition['name'] === '--help') {
            return '';
        }

        $definitionSignature = $definition['name'];

        if (!$definition['default'] && !($definition['required'] ?? true)) {
            $definitionSignature .= '?';
        }

        if (str_starts_with($definition['description'] ?? '', '...')) {
            $definitionSignature .= '*';
        }

        if ($definition['default']) {
            if (is_array($definition['default'])) {
                $definition['default'] = implode(',', $definition['default']);
            }

            $definitionSignature .= "={$definition['default']}";
        } elseif ($type === 'option' && ($definition['required'] ?? true)) {
            $definitionSignature .= '=';
        }

        if ($definition['description']) {
            $definitionSignature .= " : {$definition['description']}";
        }

        return " {{$definitionSignature}}";
    }

    /**
     * Every legacy class that fires Yii events should listen to
     * the relevant Laravel event and trigger the Yii event.
     */
    private function bootEvents(): void
    {
        /**
         * Elements
         */
        \craft\base\Element::registerEvents();
        Asset::registerEvents();
        Entry::registerEvents();
        \craft\elements\User::registerEvents();

        /**
         * FieldLayouts
         */
        BaseField::registerEvents();
        FieldLayout::registerEvents();
        FieldLayoutComponent::registerEvents();

        /**
         * Services
         */
        Addresses::registerEvents();
        Auth::registerEvents();
        Drafts::registerEvents();
        Entries::registerEvents();
        Fields::registerEvents();
        Fs::registerEvents();
        Gc::registerEvents();
        LegacySearch::registerEvents();
        Utilities::registerEvents();
        Dashboard::registerEvents();
        LegacyPlugins::registerEvents();
        LegacyProjectConfig::registerEvents();
        Revisions::registerEvents();
        Routes::registerEvents();
        Sites::registerEvents();
        Structures::registerEvents();
        SystemMessages::registerEvents();
        UserGroups::registerEvents();
        UserPermissions::registerEvents();
        Users::registerEvents();
        View::registerEvents();
        Volumes::registerEvents();
        \craft\services\ImageTransforms::registerEvents();
        \craft\imagetransforms\ImageTransformer::registerEvents();

        /**
         * Controllers
         */
        UsersController::registerEvents();

        /**
         * Utilities
         */
        AssetIndexes::registerEvents();
        ClearCaches::registerEvents();

        /**
         * Variables
         */
        Cp::registerEvents();

        Event::listen(function(RegisterCpNavItems $event) {
            if (YiiEvent::hasHandlers(CpVariable::class, 'registerCpNavItems')) {
                $yiiEvent = new RegisterCpNavItemsEvent(['navItems' => $event->navItems]);

                YiiEvent::trigger(CpVariable::class, 'registerCpNavItems', $yiiEvent);

                $event->navItems = $yiiEvent->navItems;
            }
        });

        Event::listen(function(EditionChanged $event) {
            /** @var \craft\web\Application $craft */
            $craft = app('Craft');

            // Fire an 'afterEditionChange' event
            if (!$craft->hasEventHandlers(Application::EVENT_AFTER_EDITION_CHANGE)) {
                return;
            }

            $craft->trigger(Application::EVENT_AFTER_EDITION_CHANGE, new EditionChangeEvent([
                'oldEdition' => $event->oldEdition->value,
                'newEdition' => $event->newEdition->value,
            ]));
        });

        Event::listen(Authenticated::class, function(Authenticated $event) {
            /** @var \CraftCms\Cms\User\Elements\User $user */
            $user = $event->user;
            app('Craft')->getUser()->setIdentity(new IdentityWrapper($user));
        });

        Event::listen(Login::class, function(Login $event) {
            /** @var \CraftCms\Cms\User\Elements\User $user */
            $user = $event->user;
            app('Craft')->getUser()->setIdentity(new IdentityWrapper($user));
        });

        Event::listen(Logout::class, function() {
            app('Craft')->getUser()->setIdentity(null);
        });

        Event::listen(TagsInvalidated::class, function(TagsInvalidated $event) {
            YiiTagDependency::invalidate(Craft::$app->getCache(), $event->tags);
        });

        /**
         * Deprecated concepts
         */
        Event::listen(RegisterFieldTypes::class, function(RegisterFieldTypes $event) {
            if (self::supportsCategories()) {
                $event->types->add(CategoriesField::class);
            }
            if (self::supportsTags()) {
                $event->types->add(TagsField::class);
            }
        });

        Event::listen(RegisterLinkTypes::class, function(RegisterLinkTypes $event) {
            if (self::supportsCategories()) {
                $event->types[] = CategoryLinkType::class;
            }
        });

        Event::listen(RunningGarbageCollection::class, function(RunningGarbageCollection $event) {
            $event->garbageCollection->runActions(array_filter([
                [HardDelete::class, [
                    'tables' => array_filter([
                        'categorygroups',
                        'taggroups',
                    ], fn(string $table) => Schema::hasTable($table)),
                ]],
                self::supportsCategories() ? [DeletePartialElements::class, ['elementType' => Category::class, 'table' => 'categories']] : null,
                self::supportsGlobalSets() ? [DeletePartialElements::class, ['elementType' => GlobalSet::class, 'table' => 'globalsets']] : null,
                self::supportsTags() ? [DeletePartialElements::class, ['elementType' => Tag::class, 'table' => 'tags']] : null,
                self::supportsCategories() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => Category::class, 'table' => 'categorygroups']] : null,
                self::supportsGlobalSets() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => GlobalSet::class, 'table' => 'globalsets']] : null,
                self::supportsTags() ? [DeleteOrphanedFieldLayouts::class, ['elementType' => Tag::class, 'table' => 'taggroups']] : null,
            ]));
        });

        Event::listen(SiteSaved::class, function(SiteSaved $event) {
            if (!$event->isNew || !$event->oldPrimarySiteId) {
                return;
            }

            if (self::supportsCategories()) {
                $projectConfig = app(ProjectConfig::class);
                $oldPrimarySiteUid = DB::table(Table::SITES)->uidById($event->oldPrimarySiteId);
                $existingCategorySettings = $projectConfig->get(LegacyProjectConfig::PATH_CATEGORY_GROUPS);

                if (!$projectConfig->isApplyingExternalChanges && is_array($existingCategorySettings)) {
                    foreach ($existingCategorySettings as $categoryUid => $settings) {
                        $projectConfig->set(
                            path: LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.' . $categoryUid . '.siteSettings.' . $event->site->uid,
                            value: $settings['siteSettings'][$oldPrimarySiteUid],
                            message: 'Copy site settings for category groups',
                        );
                    }
                }
            }

            $elementTypes = array_keys(array_filter([
                Category::class => self::supportsCategories(),
                GlobalSet::class => self::supportsGlobalSets(),
                Tag::class => self::supportsTags(),
            ]));

            foreach ($elementTypes as $elementType) {
                dispatch(new PropagateElements(
                    elementType: $elementType,
                    criteria: [
                        'siteId' => $event->oldPrimarySiteId,
                    ],
                    siteId: $event->site->id,
                    isNewSite: true,
                ));
            }
        });

        Event::listen(RebuildConfig::class, function(RebuildConfig $event) {
            if (self::supportsCategories()) {
                $event->config[LegacyProjectConfig::PATH_CATEGORY_GROUPS] = $this->_getCategoryGroupData();
            }
            if (self::supportsGlobalSets()) {
                $event->config[LegacyProjectConfig::PATH_GLOBAL_SETS] = $this->_getGlobalSetData();
            }
            if (self::supportsTags()) {
                $event->config[LegacyProjectConfig::PATH_TAG_GROUPS] = $this->_getTagGroupData();
            }
        });
    }

    /**
     * Return category group data config array.
     */
    private function _getCategoryGroupData(): array
    {
        return collect(Craft::$app->getCategories()->getAllGroups())
            ->mapWithKeys(fn(CategoryGroup $group) => [$group->uid => $group->getConfig()])
            ->all();
    }

    /**
     * Return tag group data config array.
     */
    private function _getTagGroupData(): array
    {
        return collect(Craft::$app->getTags()->getAllTagGroups())
            ->mapWithKeys(fn(TagGroup $group) => [$group->uid => $group->getConfig()])
            ->all();
    }

    /**
     * Return global set data config array.
     */
    private function _getGlobalSetData(): array
    {
        return collect(Craft::$app->getGlobals()->getAllSets())
            ->mapWithKeys(fn(GlobalSet $globalSet) => [$globalSet->uid => $globalSet->getConfig()])
            ->all();
    }

    public static function bootYiiEvents(): void
    {
        YiiEvent::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                if (self::supportsCategories()) {
                    $event->types[] = Category::class;
                }
                if (self::supportsGlobalSets()) {
                    $event->types[] = GlobalSet::class;
                }
                if (self::supportsTags()) {
                    $event->types[] = Tag::class;
                }
            },
        );

        YiiEvent::on(
            ArgumentManager::class,
            ArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS,
            function(RegisterGqlArgumentHandlersEvent $event) {
                if (self::supportsCategories()) {
                    $event->handlers['relatedToCategories'] = RelatedCategories::class;
                }
                if (self::supportsTags()) {
                    $event->handlers['relatedToTags'] = RelatedTags::class;
                }
            },
        );

        YiiEvent::on(
            /** @phpstan-ignore-next-line */
            ElementArguments::class,
            ElementArguments::EVENT_DEFINE_ARGUMENTS,
            function(DefineGqlArgumentsEvent $event) {
                if (self::supportsCategories()) {
                    $event->arguments['relatedToCategories'] = [
                        'name' => 'relatedToCategories',
                        // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                        'type' => Type::listOf(CategoryRelation::getType()),
                        'description' => 'Narrows the query results to elements that relate to a category list defined with this argument.',
                    ];
                }
                if (self::supportsTags()) {
                    $event->arguments['relatedToTags'] = [
                        'name' => 'relatedToTags',
                        // don't lazy load the type (see https://github.com/craftcms/cms/issues/17858)
                        'type' => Type::listOf(TagRelation::getType()),
                        'description' => 'Narrows the query results to elements that relate to a tag list defined with this argument.',
                    ];
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
            function(RegisterGqlSchemaComponentsEvent $event) {
                if (self::supportsCategories()) {
                    $label = t('Categories');
                    [$event->queries[$label], $event->mutations[$label]] = self::categorySchemaComponents();
                }

                if (self::supportsGlobalSets()) {
                    $label = t('Global Sets', category: 'yii2-adapter');
                    [$event->queries[$label], $event->mutations[$label]] = self::globalSetSchemaComponents();
                }

                if (self::supportsTags()) {
                    $label = t('Tags', category: 'yii2-adapter');
                    [$event->queries[$label], $event->mutations[$label]] = self::tagSchemaComponents();
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                if (self::supportsCategories()) {
                    array_push($event->queries, ...CategoryQuery::getQueries());
                }
                if (self::supportsGlobalSets()) {
                    array_push($event->queries, ...GlobalSetQuery::getQueries());
                }
                if (self::supportsTags()) {
                    array_push($event->queries, ...TagQuery::getQueries());
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_MUTATIONS,
            function(RegisterGqlMutationsEvent $event) {
                if (self::supportsCategories()) {
                    array_push($event->mutations, ...CategoryMutation::getMutations());
                }
                if (self::supportsGlobalSets()) {
                    array_push($event->mutations, ...GlobalSetMutation::getMutations());
                }
                if (self::supportsTags()) {
                    array_push($event->mutations, ...TagMutation::getMutations());
                }
            },
        );

        YiiEvent::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                if (self::supportsCategories()) {
                    $event->types[] = CategoryInterface::class;
                }
                if (self::supportsGlobalSets()) {
                    $event->types[] = GlobalSetInterface::class;
                }
                if (self::supportsTags()) {
                    $event->types[] = TagInterface::class;
                }
            },
        );

        YiiEvent::on(
            ElementQueryConditionBuilder::class,
            ElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS,
            function(RegisterGqlEagerLoadableFields $event) {
                if (self::supportsCategories()) {
                    $event->fieldList[ElementQueryConditionBuilder::LOCALIZED_NODENAME][] = CategoriesField::class;
                }
            },
        );

        YiiEvent::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                if (self::supportsCategories()) {
                    self::categoryPermissions($event->permissions);
                }
                if (self::supportsGlobalSets()) {
                    self::globalSetPermissions($event->permissions);
                }
            },
        );

        YiiEvent::on(
            CpVariable::class,
            CpVariable::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $newItems = [];

                if (
                    self::supportsGlobalSets() &&
                    !empty(Craft::$app->getGlobals()->getEditableSets())
                ) {
                    $newItems[] = [
                        'label' => t('Globals', category: 'yii2-adapter'),
                        'url' => 'globals',
                        'icon' => 'globe',
                    ];
                }
                if (
                    self::supportsCategories() &&
                    Craft::$app->getCategories()->getEditableGroupIds()
                ) {
                    $newItems[] = [
                        'label' => t('Categories'),
                        'url' => 'categories',
                        'icon' => 'sitemap',
                    ];
                }

                if (!empty($newItems)) {
                    // Find the last item with a "content/" URL
                    $lastContentKey = array_find_key($event->navItems, fn(array $item, int $key) => (
                        str_starts_with($item['url'], 'content/') &&
                        (!isset($event->navItems[$key + 1]) || !str_starts_with($event->navItems[$key + 1]['url'], 'content/'))
                    ));

                    if ($lastContentKey !== null) {
                        array_splice($event->navItems, $lastContentKey + 1, 0, $newItems);
                    } else {
                        array_push($event->navItems, ...$newItems);
                    }
                }
            },
        );

        YiiEvent::on(
            CpVariable::class,
            Cms::config()->allowAdminChanges ? CpVariable::EVENT_REGISTER_CP_SETTINGS : CpVariable::EVENT_REGISTER_READ_ONLY_CP_SETTINGS,
            function(RegisterCpSettingsEvent $event) {
                $label = t('Content');
                if (self::supportsGlobalSets()) {
                    $event->settings[$label]['globals'] = [
                        'iconMask' => '@craftcms/resources/icons/light/globe.svg',
                        'label' => t('Globals', category: 'yii2-adapter'),
                    ];
                }
                if (self::supportsCategories()) {
                    $event->settings[$label]['categories'] = [
                        'iconMask' => '@craftcms/resources/icons/light/sitemap.svg',
                        'label' => t('Categories'),
                    ];
                }
                if (self::supportsTags()) {
                    $event->settings[$label]['tags'] = [
                        'iconMask' => '@craftcms/resources/icons/light/tags.svg',
                        'label' => t('Tags', category: 'yii2-adapter'),
                    ];
                }
            },
        );

        YiiEvent::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                if (self::supportsCategories()) {
                    $event->rules += [
                        'categories' => 'categories/category-index',
                        'categories/<groupHandle:{handle}>' => 'categories/category-index',
                        'categories/<groupHandle:{handle}>/new' => 'categories/create',
                        'categories/<groupHandle:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
                        'settings/categories' => 'categories/group-index',
                        'settings/categories/new' => 'categories/edit-category-group',
                        'settings/categories/<groupId:\d+>' => 'categories/edit-category-group',
                    ];
                }

                if (self::supportsGlobalSets()) {
                    $event->rules += [
                        'globals' => 'globals',
                        'globals/<globalSetHandle:{handle}>' => 'globals/edit-content',
                        'settings/globals' => 'system-settings/global-set-index',
                        'settings/globals/new' => 'system-settings/edit-global-set',
                        'settings/globals/<globalSetId:\d+>' => 'system-settings/edit-global-set',
                    ];
                }

                if (self::supportsTags()) {
                    $event->rules += [
                        'settings/tags' => 'tags/index',
                        'settings/tags/new' => 'tags/edit-tag-group',
                        'settings/tags/<tagGroupId:\d+>' => 'tags/edit-tag-group',
                    ];
                }
            },
        );

        YiiEvent::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['yii2-adapter'] = dirname(__DIR__) . '/resources/templates';
        });

        if (self::supportsCategories()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
                ->onUpdate(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
                ->onRemove(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleDeletedCategoryGroup($event));
        }

        if (self::supportsGlobalSets()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleChangedGlobalSet($event))
                ->onUpdate(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleChangedGlobalSet($event))
                ->onRemove(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleDeletedGlobalSet($event));

            Twig::registerExtension(new GlobalsExtension(), TemplateMode::Site);
        }

        // Legacy `view` global remains available through the adapter layer only.
        Twig::registerExtension(new Extension());

        Event::listen(function(DefineNativeFields $event) {
            switch ($event->fieldLayout->type) {
                case Category::class:
                case Tag::class:
                    $event->fields[] = TitleField::class;
                    break;
            }
        });

        if (self::supportsTags()) {
            app(ProjectConfig::class)
                ->onAdd(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event))
                ->onUpdate(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event))
                ->onRemove(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleDeletedTagGroup($event));
        }
    }

    private static ?bool $supportsCategories = null;

    private static ?bool $supportsGlobalSets = null;

    private static ?bool $supportsTags = null;

    public static function supportsCategories(): bool
    {
        return self::$supportsCategories ??= self::supports('categories');
    }

    public static function supportsGlobalSets(): bool
    {
        return self::$supportsGlobalSets ??= self::supports('globalsets');
    }

    public static function supportsTags(): bool
    {
        return self::$supportsTags ??= self::supports('tags');
    }

    private static function supports(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function resetSupport(): void
    {
        self::$supportsCategories = null;
        self::$supportsGlobalSets = null;
        self::$supportsTags = null;
    }

    /**
     * Return category group permissions.
     */
    private static function categorySchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroup) {
                $name = t($categoryGroup->name, category: 'site');
                $prefix = "categorygroups.$categoryGroup->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for categories in the “{name}” category group', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit categories in the “{categoryGroup}” category group', [
                        'categoryGroup' => $name,
                    ], 'yii2-adapter'),
                    'nested' => [
                        "$prefix:save" => [
                            'label' => t('Save categories in the “{categoryGroup}” category group', [
                                'categoryGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                        "$prefix:delete" => [
                            'label' => t('Delete categories from the “{categoryGroup}” category group', [
                                'categoryGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                    ],
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    /**
     * Return global set permissions.
     */
    private static function globalSetSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!empty($globalSets)) {
            foreach ($globalSets as $globalSet) {
                $name = t($globalSet->name, category: 'site');
                $prefix = "globalsets.$globalSet->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for the “{name}” global set', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit the “{globalSet}” global set.', [
                        'globalSet' => $name,
                    ], 'yii2-adapter'),
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    /**
     * Return tag group permissions.
     */
    private static function tagSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($tagGroups)) {
            foreach ($tagGroups as $tagGroup) {
                $name = t($tagGroup->name, category: 'site');
                $prefix = "taggroups.$tagGroup->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for tags in the “{name}” tag group', [
                        'name' => $name,
                    ], 'yii2-adapter'),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit tags in the “{tagGroup}” tag group', [
                        'tagGroup' => $name,
                    ], 'yii2-adapter'),
                    'nested' => [
                        "$prefix:save" => [
                            'label' => t('Save tags in the “{tagGroup}” tag group', [
                                'tagGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                        "$prefix:delete" => [
                            'label' => t('Delete tags from the “{tagGroup}” tag group', [
                                'tagGroup' => $name,
                            ], 'yii2-adapter'),
                        ],
                    ],
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    private static function categoryPermissions(array &$permissions): void
    {
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!$categoryGroups) {
            return;
        }

        $type = Category::pluralLowerDisplayName();

        foreach ($categoryGroups as $group) {
            $permissions[] = [
                'heading' => t('Category Group - {name}', [
                    'name' => t($group->name, category: 'site'),
                ], 'yii2-adapter'),
                'permissions' => [
                    "viewCategories:$group->uid" => [
                        'label' => mb_ucfirst(t('View {type}', ['type' => $type])),
                        'nested' => [
                            "saveCategories:$group->uid" => [
                                'label' => mb_ucfirst(t('Save {type}', ['type' => $type])),
                            ],
                            "deleteCategories:$group->uid" => [
                                'label' => mb_ucfirst(t('Delete {type}', ['type' => $type])),
                            ],
                            "viewPeerCategoryDrafts:$group->uid" => [
                                'label' => mb_ucfirst(t('View other users’ {type}', [
                                    'type' => t('drafts'),
                                ])),
                                'nested' => [
                                    "savePeerCategoryDrafts:$group->uid" => [
                                        'label' => mb_ucfirst(t('Save other users’ {type}', [
                                            'type' => t('drafts'),
                                        ])),
                                    ],
                                    "deletePeerCategoryDrafts:$group->uid" => [
                                        'label' => t('Delete other users’ {type}', [
                                            'type' => t('drafts'),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    }

    private static function globalSetPermissions(array &$permissions): void
    {
        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!$globalSets) {
            return;
        }

        $globalSetPermissions = [];

        foreach ($globalSets as $globalSet) {
            $globalSetPermissions["editGlobalSet:$globalSet->uid"] = [
                'label' => t('Edit “{title}”', [
                    'title' => t($globalSet->name, category: 'site'),
                ]),
            ];
        }

        $permissions[] = [
            'heading' => t('Global Sets', category: 'yii2-adapter'),
            'permissions' => $globalSetPermissions,
        ];
    }

    /**
     * Check if we're dealing with an older migrations table.
     * In that case we'll need to migrate this on the fly.
     */
    private function ensureNewMigrationTable(): void
    {
        try {
            if (app()->environment('workbench') || app()->environment('testing')) {
                return;
            }

            if (Schema::hasColumn(Table::MIGRATIONS, 'migration')) {
                return;
            }

            if (!Cms::config()->allowAdminChanges) {
                throw new RuntimeException('The migration table has the wrong schema structure and allowAdminChanges is disabled. Run `php craft migrate:migration-table` to migrate the table to the new format.');
            }

            Artisan::call('craft:migrate:migration-table', [
                '--force' => true,
            ]);
        } catch (PDOException) {
            // No database connection
        }
    }

    /**
     * Check if we're dealing with an older sessions table.
     * In that case we'll need to migrate this on the fly.
     */
    private function ensureNewSessionsTable(): void
    {
        try {
            if (app()->environment('workbench') || app()->environment('testing')) {
                return;
            }

            if (Schema::hasColumn(Table::SESSIONS, 'payload')) {
                return;
            }

            if (!Cms::config()->allowAdminChanges) {
                throw new RuntimeException('The sessions table has the wrong schema structure and allowAdminChanges is disabled. Run `php craft migrate:sessions-table` to migrate the table to the new format.');
            }

            Artisan::call('craft:migrate:sessions-table', [
                '--force' => true,
            ]);
        } catch (PDOException) {
            // No database connection
        }
    }
}
