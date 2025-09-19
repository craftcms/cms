<?php

namespace CraftCms\Yii2Adapter;

use craft\console\controllers\HelpController;
use craft\services\Dashboard;
use craft\services\Plugins as LegacyPlugins;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\Utilities;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use CraftCms\Yii2Adapter\Console\MigrateMigrationTableCommand;
use CraftCms\Yii2Adapter\Http\Controller;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use yii\BaseYii;

class Yii2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConstants();
        $this->registerLegacyApp();

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->setLaravelDefaults();
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

            \Craft::$app = $app;

            $this->bootEvents();

            return $app;
        });
    }

    /**
     * Set some compatible Laravel defaults if the environment variables aren't set.
     */
    protected function setLaravelDefaults(): void
    {
        Config::set('app.debug', Env::get('APP_DEBUG', Env::get('CRAFT_DEV_MODE', false)));
        Config::set('app.env', Env::get('APP_ENV', Env::get('CRAFT_ENVIRONMENT', Env::get('ENVIRONMENT', 'local'))));
        Config::set('session.driver', Env::get('SESSION_DRIVER', 'file'));
        Config::set('cache.default', Env::get('CACHE_STORE', 'file'));
        Config::set('database.default', Env::get('DB_CONNECTION', Env::get('CRAFT_DB_DRIVER', 'mysql')));
    }

    public function boot(): void
    {
        $this->commands([
            MigrateMigrationTableCommand::class,
        ]);

        /**
         * Prefix is not generally a configuration variable that
         * is set through the environment in Laravel, so
         * we set it here for backwards compatibility.
         */
        $connection = Config::get('database.default');
        Config::set("database.connections.{$connection}.prefix", Env::get('DB_TABLE_PREFIX'));

        /**
         * Load Craft
         */
        app('Craft');

        $this->ensureNewMigrationTable();

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->bootLegacyCommands();
    }

    private function bootLegacyCommands(): void
    {
        /** @var \craft\console\Application $app */
        $app = app('Craft');

        $controller = new HelpController('help', $app);
        $commands = $controller->allCommandsInfo();

        foreach ($commands as $command) {
            if (str_contains($command['description'], '. ')) {
                $command['description'] = Str::before($command['description'], ". ") . '. ';
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
            $definitionSignature .= "=";
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
         * Services
         */
        Utilities::registerEvents();
        Dashboard::registerEvents();
        LegacyPlugins::registerEvents();
        ProjectConfig::registerEvents();
        SystemMessages::registerEvents();

        /**
         * Utilities
         */
        AssetIndexes::registerEvents();
        ClearCaches::registerEvents();

        Event::listen(Login::class, function(Login $event) {
            $user = app('Craft')->getUsers()->getUserById($event->user->getAuthIdentifier());

            app('Craft')->getUser()->setIdentity($user);
        });

        Event::listen(Logout::class, function() {
            app('Craft')->getUser()->setIdentity(null);
        });
    }

    /**
     * Check if we're dealing with an older migrations table.
     * In that case we'll need to migrate this on the fly.
     */
    private function ensureNewMigrationTable(): void
    {
        try {
            if (Schema::hasColumn(Table::MIGRATIONS, 'migration')) {
                return;
            }

            if (!app(GeneralConfig::class)->allowAdminChanges) {
                throw new RuntimeException('The migration table has the wrong schema structure and allowAdminChanges is disabled. Run `php craft migrate:migration-table` to migrate the table to the new format.');
            }

            Artisan::call('craft:migrate:migration-table', [
                '--force' => true,
            ]);
        } catch (\PDOException) {
            // No database connection
        }
    }
}
