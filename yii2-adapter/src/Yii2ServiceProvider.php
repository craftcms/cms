<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Craft;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use craft\events\ExceptionEvent;
use craft\events\RedirectEvent;
use craft\web\ErrorHandler;
use craft\web\RedirectRule;
use craft\web\twig\variables\CraftVariable as LegacyCraftVariable;
use CraftCms\Cms\Asset\AssetFileKinds;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Events\VolumeConfigPreparing;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Settings;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\MigrationRepository;
use CraftCms\Cms\Database\Migrator as CoreMigrator;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementEditorPayloadResolving;
use CraftCms\Cms\Field\Events\FieldCachesInvalidated;
use CraftCms\Cms\Field\Events\MatrixBlockHtmlRendering;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutElementControlResolving;
use CraftCms\Cms\Gql\AssetTransformContext;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlTypes;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Route\Events\Redirecting;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\Ui\UiControlTypes;
use CraftCms\Cms\Ui\UiNodeTypes;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Utility\UtilityTypes;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use CraftCms\Yii2Adapter\Asset\LegacyAssetFileKinds;
use CraftCms\Yii2Adapter\Asset\LegacyAssetTransforms;
use CraftCms\Yii2Adapter\Config\GeneralConfigCompatibility;
use CraftCms\Yii2Adapter\Config\MultiEnvironmentConfigCompatibility;
use CraftCms\Yii2Adapter\Console\AddCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\AddGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\AddTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropCategoriesSupportCommand;
use CraftCms\Yii2Adapter\Console\DropGlobalSetsSupportCommand;
use CraftCms\Yii2Adapter\Console\DropTagsSupportCommand;
use CraftCms\Yii2Adapter\Console\LegacyCommandCompatibility;
use CraftCms\Yii2Adapter\Console\MigrateSessionsTableCommand;
use CraftCms\Yii2Adapter\Console\OffCommand;
use CraftCms\Yii2Adapter\Console\OnCommand;
use CraftCms\Yii2Adapter\Console\RepairCategoryGroupStructureCommand;
use CraftCms\Yii2Adapter\Cp\Html\LegacyMenuHtml;
use CraftCms\Yii2Adapter\Cp\LegacySettings;
use CraftCms\Yii2Adapter\Database\Migrator;
use CraftCms\Yii2Adapter\Element\LegacyElementEditorScreen;
use CraftCms\Yii2Adapter\Field\MatrixBlockHtml;
use CraftCms\Yii2Adapter\FieldLayout\LegacyLayoutElementHtml;
use CraftCms\Yii2Adapter\Filesystem\FilesystemCompatibility;
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
use CraftCms\Yii2Adapter\Mail\LegacyTransportCompatibility;
use CraftCms\Yii2Adapter\Mail\TestToEmailAddressCompatibility;
use CraftCms\Yii2Adapter\Mixins\CraftVariableMixin;
use CraftCms\Yii2Adapter\SystemMessage\LegacySystemMessages;
use CraftCms\Yii2Adapter\Ui\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Ui\LegacyConditions;
use CraftCms\Yii2Adapter\Ui\Nodes\LegacyHtmlField;
use CraftCms\Yii2Adapter\User\LegacyUserPermissions;
use CraftCms\Yii2Adapter\Utility\LegacyUtilityTypes;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Queue\Events\JobAttempted;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Override;
use PDOException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use yii\base\Application as YiiApplication;
use yii\base\Behavior;
use yii\base\ExitException;
use yii\web\HttpException as YiiHttpException;
use yii\web\NotFoundHttpException as YiiNotFoundHttpException;

class Yii2ServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(CoreMigrator::class, Migrator::class);
        $this->app
            ->when(Migrator::class)
            ->needs(MigrationRepositoryInterface::class)
            ->give(fn() => $this->app->make(MigrationRepository::class, ['table' => Table::MIGRATIONS]));

        new ClassAliases()->register();
        new MultiEnvironmentConfigCompatibility()->register($this->app);

        LegacyGeneralConfig::registerBootingCallback($this->app);

        new GeneralConfigCompatibility()->register($this->app);

        $this->registerConstants();

        new LegacyApp()->register($this->app);
        new CompatibilityMixins()->register();
        Event::listen(MatrixBlockHtmlRendering::class, MatrixBlockHtml::class);
        Event::listen(VolumeConfigPreparing::class, function(VolumeConfigPreparing $event): void {
            $event->config['transformFs'] = $event->volume->getTransformFsHandle(false);
            $event->config['transformSubpath'] = $event->volume->getTransformSubpath(false, false);
        });
        new FilesystemCompatibility()->register($this->app);
        new LegacyTransportCompatibility()->register($this->app);
        $this->app->singleton(AssetFileKinds::class, LegacyAssetFileKinds::class);
        $this->app->singleton(Settings::class, LegacySettings::class);
        $this->app->singleton(MenuHtml::class, LegacyMenuHtml::class);
        $this->app->singleton(GqlArguments::class, LegacyGqlArguments::class);
        $this->app->singleton(GqlDirectives::class, LegacyGqlDirectives::class);
        $this->app->singleton(GqlTypes::class, LegacyGqlTypes::class);
        Event::listen(AssetUrlResolving::class, function(AssetUrlResolving $event): void {
            $state = $this->app->make(AssetTransformContext::class)->get($event->asset);

            if ($state === null || $event->transform === null || $event->url !== null || $event->handled) {
                return;
            }

            try {
                $event->url = $this->app->make(AssetTransformers::class)->transform(
                    $event->asset,
                    $event->transform,
                )->url;
            } catch (AssetTransformException|NotSupportedException $exception) {
                report($exception);
            }

            $event->handled = true;
        });
        Event::listen(AssetUrlResolving::class, [LegacyAssetTransforms::class, 'handleUrlResolving']);
        $this->app->scoped(Gql::class, LegacyGql::class);
        $this->app->scoped(SystemMessages::class, LegacySystemMessages::class);
        $this->app->scoped(UserPermissions::class, LegacyUserPermissions::class);
        $this->app->singleton(UtilityTypes::class, LegacyUtilityTypes::class);
        $this->app->singleton(Conditions::class, LegacyConditions::class);
        $this->callAfterResolving(UiNodeTypes::class, fn(UiNodeTypes $types) => $types->register(LegacyHtmlField::class));
        $this->callAfterResolving(UiControlTypes::class, fn(UiControlTypes $types) => $types->register(LegacyHtmlControl::class));
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
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Cp, '_components/fieldtypes/Matrix', __DIR__ . '/../resources/templates/_components/fieldtypes/Matrix');
        // Legacy relation field settings templates, which plugin settings templates extend.
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Cp, '_components/fieldtypes', __DIR__ . '/../resources/templates/_components/fieldtypes');
        // Core templates resolve first, so this only serves the legacy form includes core no longer ships.
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Cp, '_includes', __DIR__ . '/../resources/templates/_includes');
        $this->registerExceptionHandling();
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
        defined('CRAFT_LICENSE_KEY_PATH') || define('CRAFT_LICENSE_KEY_PATH', Env::get('CRAFT_LICENSE_KEY_PATH') ?: config_path('craft/license.key'));
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

        /**
         * Craft 6 reads the table prefix from `DB_PREFIX`. Craft 5 sites may still set it with
         * `CRAFT_DB_TABLE_PREFIX` or `DB_TABLE_PREFIX`, which Craft 5 always suffixed with an underscore.
         * This runs before the connection is resolved, so the prefix applies to it and to `db2`.
         */
        $connection = Config::get('database.default');
        $legacyPrefix = Env::get('CRAFT_DB_TABLE_PREFIX') ?: Env::get('DB_TABLE_PREFIX');

        if (!Config::get("database.connections.{$connection}.prefix") && $legacyPrefix) {
            Config::set("database.connections.{$connection}.prefix", Str::finish($legacyPrefix, '_'));
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

        Event::listen(Redirecting::class, function(Redirecting $event) {
            if (!class_exists(Craft::class) || !Craft::$app) {
                return;
            }

            $errorHandler = Craft::$app->getErrorHandler();

            if (!$errorHandler->hasEventHandlers(ErrorHandler::EVENT_BEFORE_REDIRECT)) {
                return;
            }

            $errorHandler->trigger(ErrorHandler::EVENT_BEFORE_REDIRECT, new RedirectEvent([
                'rule' => new RedirectRule(array_filter([
                    'from' => $event->rule->from,
                    'to' => $event->rule->to,
                    'statusCode' => $event->rule->statusCode,
                    'caseSensitive' => $event->rule->caseSensitive,
                    'match' => $event->rule->match,
                ], fn($value) => $value !== null)),
            ]));
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

        if ($exception->getPrevious() instanceof YiiHttpException) {
            return $exception->getPrevious();
        }

        if ($exception->getStatusCode() === 404) {
            return new YiiNotFoundHttpException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return new YiiHttpException($exception->getStatusCode(), $exception->getMessage(), $exception->getCode(), $exception);
    }

    public function boot(): void
    {
        Event::listen(ElementEditorPayloadResolving::class, LegacyElementEditorScreen::class);
        Event::listen(FieldLayoutElementControlResolving::class, LegacyLayoutElementHtml::class);

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
            MigrateSessionsTableCommand::class,
            OffCommand::class,
            OnCommand::class,
            RepairCategoryGroupStructureCommand::class,
        ]);

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

        $this->app->terminating(function(): void {
            $this->triggerAfterRequest();
            Craft::getLogger()->flush(true);
        });

        // A worker runs many jobs, so onAfterRequest() callbacks run after the job that added them, as Laravel's
        // defer() callbacks do. EVENT_AFTER_REQUEST handlers still fire once, as they'd fire again after every job.
        Event::listen(function(JobAttempted $event): void {
            if (in_array($event->connectionName, ['sync', 'deferred'], true) || !Craft::$app instanceof YiiApplication) {
                return;
            }

            Craft::$app->runAfterRequestCallbacks();
        });

        if (!$this->app->runningInConsole()) {
            return;
        }

        new LegacyCommandCompatibility()->boot();
    }

    private function triggerAfterRequest(): void
    {
        if (!Craft::$app instanceof YiiApplication || Craft::$app->state >= YiiApplication::STATE_AFTER_REQUEST) {
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

            foreach ($legacyVariable->getBehaviors() as $behavior) {
                $this->registerCraftVariableBehavior($behavior);
            }
        });
    }

    /**
     * Exposes the public properties and methods of a behavior attached to the legacy `craft` variable,
     * such as `craft.commerce` or `craft.shopifyProducts()`.
     */
    private function registerCraftVariableBehavior(Behavior $behavior): void
    {
        $reflection = new ReflectionObject($behavior);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (!$property->isStatic() && !property_exists(Behavior::class, $name)) {
                CraftVariable::macro($name, fn() => $behavior->$name);
            }
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if (!$method->isStatic() && !method_exists(Behavior::class, $name)) {
                CraftVariable::macro($name, fn(mixed ...$args) => $behavior->$name(...$args));
            }
        }
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
