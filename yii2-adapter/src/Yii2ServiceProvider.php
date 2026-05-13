<?php

namespace CraftCms\Yii2Adapter;

use Craft;
use craft\events\ExceptionEvent;
use craft\web\Application as WebApplication;
use craft\web\ErrorHandler;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Events\FieldCachesInvalidated;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Twig\Variables\CraftVariable;
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
use CraftCms\Yii2Adapter\Filesystem\FilesystemCompatibility;
use CraftCms\Yii2Adapter\HtmlPurifier\LegacyHtmlPurifierConfigRegistrar;
use CraftCms\Yii2Adapter\Http\LegacyMiddleware;
use CraftCms\Yii2Adapter\I18N\I18NCompatibility;
use CraftCms\Yii2Adapter\Mail\TestToEmailAddressCompatibility;
use CraftCms\Yii2Adapter\Mixins\CraftVariableMixin;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
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
    public function register(): void
    {
        new ClassAliases()->register();
        new MultiEnvironmentConfigCompatibility()->register($this->app);

        $this->registerConstants();

        new LegacyApp()->register($this->app);
        new CompatibilityMixins()->register();
        new FilesystemCompatibility()->register($this->app);

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
        $handler->renderable(function(Throwable $exception): null {
            $this->triggerLegacyBeforeHandleException($exception);

            return null;
        });
    }

    private function triggerLegacyBeforeHandleException(Throwable $exception): void
    {
        if ($exception instanceof ExitException || !Craft::$app) {
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
        app(LegacyHtmlPurifierConfigRegistrar::class)->boot();

        /**
         * Load legacy Craft
         */
        app('Craft');

        new RebrandCompatibility()->boot();

        CraftVariable::mixin(new CraftVariableMixin());

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
