<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Composer\InstalledVersions;
use Craft;
use craft\events\ExceptionEvent;
use craft\web\Application as WebApplication;
use craft\web\ErrorHandler;
use craft\web\twig\variables\CraftVariable as LegacyCraftVariable;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\AssetFileKinds;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Settings;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\MigrationRepository;
use CraftCms\Cms\Database\Migrator as CoreMigrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Events\FieldCachesInvalidated;
use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormNodeTypes;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlTypes;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Composer as CoreComposer;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Utility\UtilityTypes;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use CraftCms\Yii2Adapter\Asset\LegacyAssetFileKinds;
use CraftCms\Yii2Adapter\Config\GeneralConfigCompatibility;
use CraftCms\Yii2Adapter\Config\MultiEnvironmentConfigCompatibility;
use CraftCms\Yii2Adapter\Console\AddCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\AddGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\AddTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\DropGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\LegacyCommandCompatibility;
use CraftCms\Yii2Adapter\Console\MigrateMigrationTableCommand;
use CraftCms\Yii2Adapter\Console\MigrateSessionsTableCommand;
use CraftCms\Yii2Adapter\Console\RepairCategoryGroupStructureCommand;
use CraftCms\Yii2Adapter\Cp\LegacySettings;
use CraftCms\Yii2Adapter\Database\Migrator;
use CraftCms\Yii2Adapter\Filesystem\FilesystemCompatibility;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Form\Nodes\LegacyHtmlField;
use CraftCms\Yii2Adapter\Gql\LegacyGql;
use CraftCms\Yii2Adapter\Gql\LegacyGqlArguments;
use CraftCms\Yii2Adapter\Gql\LegacyGqlDirectives;
use CraftCms\Yii2Adapter\Gql\LegacyGqlTypes;
use CraftCms\Yii2Adapter\HtmlPurifier\LegacyHtmlPurifierConfigRegistrar;
use CraftCms\Yii2Adapter\Http\CaptureOriginalActionRequestUri;
use CraftCms\Yii2Adapter\Http\HandleYiiSiteRouteFallback;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use CraftCms\Yii2Adapter\Http\NormalizeLegacyPath;
use CraftCms\Yii2Adapter\Http\PrepareLegacyCraftApp;
use CraftCms\Yii2Adapter\Http\RegisterLegacyCompatAssets;
use CraftCms\Yii2Adapter\I18N\I18NCompatibility;
use CraftCms\Yii2Adapter\Mail\TestToEmailAddressCompatibility;
use CraftCms\Yii2Adapter\Mixins\CraftVariableMixin;
use CraftCms\Yii2Adapter\Plugin\Plugins as LegacyPlugins;
use CraftCms\Yii2Adapter\Support\Composer;
use CraftCms\Yii2Adapter\SystemMessage\LegacySystemMessages;
use CraftCms\Yii2Adapter\Twig\AliasesExtension;
use CraftCms\Yii2Adapter\User\LegacyUserPermissions;
use CraftCms\Yii2Adapter\Utility\LegacyUtilityTypes;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Override;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use yii\base\Application as YiiApplication;
use yii\base\ExitException;
use yii\web\HttpException as YiiHttpException;
use yii\web\NotFoundHttpException as YiiNotFoundHttpException;

class Yii2ServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerAliases();

        $this->app->bind(CoreMigrator::class, Migrator::class);
        $this->app
            ->when(Migrator::class)
            ->needs(MigrationRepositoryInterface::class)
            ->give(fn() => $this->app->make(MigrationRepository::class, ['table' => Table::MIGRATIONS]));

        new ClassAliases()->register();
        new MultiEnvironmentConfigCompatibility()->register($this->app);

        $appType = $this->app->runningInConsole() ? 'console' : 'web';
        Config::set('craft.general', new GeneralConfigCompatibility()->convert(
            Config::get('craft.general', []),
            Config::get("craft.general.{$appType}"),
        ));

        $this->registerConstants();

        new LegacyApp()->register($this->app);
        new CompatibilityMixins()->register();
        new FilesystemCompatibility()->register($this->app);
        $this->app->singleton(AssetFileKinds::class, LegacyAssetFileKinds::class);
        $this->app->singleton(Settings::class, LegacySettings::class);
        $this->app->singleton(GqlArguments::class, LegacyGqlArguments::class);
        $this->app->singleton(GqlDirectives::class, LegacyGqlDirectives::class);
        $this->app->singleton(GqlTypes::class, LegacyGqlTypes::class);
        $this->app->scoped(Gql::class, LegacyGql::class);
        $this->app->scoped(SystemMessages::class, LegacySystemMessages::class);
        $this->app->scoped(UserPermissions::class, LegacyUserPermissions::class);
        $this->app->singleton(UtilityTypes::class, LegacyUtilityTypes::class);
        $this->app->singleton(Plugins::class, LegacyPlugins::class);
        $this->app->singleton(CoreComposer::class, Composer::class);
        $this->callAfterResolving(FormNodeTypes::class, fn(FormNodeTypes $types) => $types->register(LegacyHtmlField::class));
        $this->callAfterResolving(FormControlTypes::class, fn(FormControlTypes $types) => $types->register(LegacyHtmlControl::class));
        /**
         * Load the legacy fallback route from booted() so it registers after
         * the CMS package's own Route::fallback(), ensuring that unmatched
         * requests are forwarded to the legacy Yii application (where any
         * URL rules registered via UrlManager::EVENT_REGISTER_CP_URL_RULES
         * and EVENT_REGISTER_SITE_URL_RULES are honored).
         */
        $this->app->booted(function(): void {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        $this->setLaravelDefaults();
        $this->registerLegacySiteTemplateRoot();
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Cp, 'yii2-adapter', __DIR__ . '/../resources/templates');
        $this->registerExceptionHandling();
    }

    private function registerAliases(): void
    {
        $cmsPath = InstalledVersions::getInstallPath('craftcms/cms') ?? dirname(__DIR__, 2);
        $iconsPath = CmsAssets::resourcesPath('icons');

        Aliases::set('@root', Env::get('CRAFT_ROOT_PATH', $this->app->basePath()));
        Aliases::set('@craftcms', $cmsPath);
        Aliases::set('@cmsAssets', CmsAssets::packagePath());
        Aliases::set('@package', "$cmsPath/src");
        Aliases::set('@resources', "$cmsPath/resources");
        Aliases::set('@vendor', $this->app->basePath('vendor'));
        Aliases::set('@storage', $this->app->storagePath());
        Aliases::set('@runtime', $this->app->storagePath('runtime'));
        Aliases::set('@templates', is_dir($this->app->resourcePath('views'))
            ? $this->app->resourcePath('views')
            : $this->app->basePath('templates'));
        Aliases::set('@web', Env::get('CRAFT_WEB_URL', config('app.url')));
        Aliases::set('@icons', $iconsPath);

        $iconAliases = ['@appicons' => "$iconsPath/solid"];

        foreach (['brands', 'regular', 'custom-icons'] as $family) {
            foreach (glob("$iconsPath/$family/*.svg") ?: [] as $path) {
                $iconAliases['@appicons/' . basename($path)] = $path;
            }
        }

        foreach ([
            'alert.svg' => 'solid/triangle-exclamation.svg',
            'broken-image' => 'solid/image-slash.svg',
            'buoey.svg' => 'solid/life-ring.svg',
            'draft.svg' => 'solid/scribble.svg',
            'entry-types' => 'solid/files.svg',
            'excite.svg' => 'solid/certificate.svg',
            'feed.svg' => 'solid/rss.svg',
            'field.svg' => 'solid/pen-to-square.svg',
            'hash.svg' => 'solid/hashtag.svg',
            'info-circle' => 'solid/circle-info.svg',
            'info-circle.svg' => 'solid/circle-info.svg',
            'info.svg' => 'solid/circle-info.svg',
            'location.svg' => 'solid/location-dot.svg',
            'photo.svg' => 'solid/image.svg',
            'plugin.svg' => 'solid/plug.svg',
            'routes.svg' => 'solid/signs-post.svg',
            'search.svg' => 'solid/magnifying-glass.svg',
            'shopping-cart' => 'solid/cart-shopping.svg',
            'template.svg' => 'solid/file-code.svg',
            'tip.svg' => 'solid/lightbulb.svg',
            'tools.svg' => 'solid/screwdriver-wrench.svg',
            'tree.svg' => 'solid/sitemap.svg',
            'upgrade.svg' => 'solid/square-arrow-up.svg',
            'wand.svg' => 'solid/wand-magic-sparkles.svg',
            'world.svg' => 'solid/earth-americas.svg',
        ] as $name => $path) {
            $iconAliases["@appicons/$name"] = "$iconsPath/$path";
        }

        foreach ($iconAliases as $alias => $path) {
            Aliases::set($alias, $path);
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
    }

    private function registerLegacySiteTemplateRoot(): void
    {
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Site, '', base_path('templates'));
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

    protected function registerExceptionHandling(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (!$handler instanceof Handler) {
            return;
        }

        $handler->dontReport([ExitException::class]);
        $handler->renderable(fn(ExitException $exception) => LegacyMiddleware::createResponse());
        $handler->renderable(function(Throwable $exception) {
            $this->triggerLegacyBeforeHandleException($exception);

            $response = class_exists(Craft::class) ? Craft::$app?->getResponse() : null;

            if ($response?->isSent || $response?->getIsRedirection()) {
                return LegacyMiddleware::createResponse();
            }

            return null;
        });
    }

    private function triggerLegacyBeforeHandleException(Throwable $exception): void
    {
        if ($exception instanceof ExitException || !class_exists(Craft::class) || !Craft::$app) {
            return;
        }

        $errorHandler = Craft::$app->getErrorHandler();

        if (!$errorHandler->hasEventHandlers(ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION)) {
            return;
        }

        $errorHandler->trigger(ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, new ExceptionEvent([
            'exception' => $this->toLegacyException($exception),
        ]));
    }

    private function toLegacyException(Throwable $exception): Throwable
    {
        if (!$exception instanceof HttpExceptionInterface) {
            return $exception;
        }

        if ($exception->getStatusCode() === 404) {
            return new YiiNotFoundHttpException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return new YiiHttpException($exception->getStatusCode(), $exception->getMessage(), $exception->getCode(), $exception);
    }

    public function boot(): void
    {
        $this->app->make(Twig::class)->registerExtension(new AliasesExtension());

        $kernel = $this->app->make(HttpKernel::class);
        $middleware = array_values(array_filter(
            $kernel->getGlobalMiddleware(),
            fn(string $middleware) => $middleware !== HandleActionRequest::class,
        ));
        $tokenIndex = array_search(HandleTokenRequest::class, $middleware, true);

        array_splice($middleware, $tokenIndex === false ? 0 : $tokenIndex + 1, 0, [
            NormalizeLegacyPath::class,
            HandleActionRequest::class,
        ]);

        $kernel->setGlobalMiddleware($middleware);
        $kernel->prependMiddleware(CaptureOriginalActionRequestUri::class);
        $this->app->make(Router::class)->pushMiddlewareToGroup('craft', PrepareLegacyCraftApp::class);
        $this->app->make(Router::class)->pushMiddlewareToGroup('craft.web', HandleYiiSiteRouteFallback::class);
        $this->app->make(Router::class)->pushMiddlewareToGroup('craft.cp', RegisterLegacyCompatAssets::class);

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

        new I18NCompatibility()->boot();
        new TestToEmailAddressCompatibility()->boot();
        $this->app->make(LegacyHtmlPurifierConfigRegistrar::class)->boot();

        /**
         * Load legacy Craft
         */
        app('Craft');

        new RebrandCompatibility()->boot();

        CraftVariable::mixin(new CraftVariableMixin());
        $this->registerCraftVariableCompatibility();

        /**
         * Keep legacy CustomFieldBehavior statics in sync when field caches are invalidated.
         */
        Event::listen(FieldCachesInvalidated::class, fn() => Craft::populateCustomFieldBehavior());

        $this->app->booted(function() {
            $this->ensureNewMigrationTable();
            $this->ensureNewSessionsTable();
        });

        $this->app->terminating(fn() => $this->triggerAfterRequestForLaravelRequest());

        if (!$this->app->runningInConsole()) {
            return;
        }

        new LegacyCommandCompatibility()->boot();
    }

    private function triggerAfterRequestForLaravelRequest(): void
    {
        if (!Craft::$app instanceof WebApplication) {
            return;
        }

        if (Craft::$app->state >= YiiApplication::STATE_AFTER_REQUEST) {
            return;
        }

        Craft::$app->state = YiiApplication::STATE_AFTER_REQUEST;
        Craft::$app->trigger(YiiApplication::EVENT_AFTER_REQUEST);
        Craft::$app->state = YiiApplication::STATE_END;
    }

    private function registerCraftVariableCompatibility(): void
    {
        $this->app->afterResolving(CraftVariable::class, function() {
            $legacyVariable = new LegacyCraftVariable();

            foreach (array_keys($legacyVariable->getComponents()) as $name) {
                CraftVariable::macro($name, fn() => $legacyVariable->get($name));
            }
        });
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

            if (!Schema::hasTable(Table::SESSIONS)) {
                app(LaravelMigrations::class)->ensureSessionsTable();

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
