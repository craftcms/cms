<?php

namespace CraftCms\Yii2Adapter;

use craft\console\controllers\HelpController;
use craft\helpers\App;
use craft\services\Utilities;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use Exception;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use yii\BaseYii;

class Yii2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        /*
         * This is to prevent Yii from running exit(), we want to catch Yii
         * exiting when for example a redirect is executed.
         */
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        /**
         * Set some base CRAFT variables to their Laravel equivalents.
         */
        defined('YII_DEBUG') || define('YII_DEBUG', config('app.debug'));

        if (is_dir(resource_path('views'))) {
            defined('CRAFT_TEMPLATES_PATH') || define('CRAFT_TEMPLATES_PATH', resource_path('views'));
        }

        defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', base_path());
        defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH . '/vendor');

        // Are we in a Laravel skeleton?
        if (is_dir(config_path('craft')) || file_exists(config_path('auth.php'))) {
            defined('CRAFT_CONFIG_PATH') || define('CRAFT_CONFIG_PATH', config_path('craft'));
        }

        if (in_array(DB::connection()->getDriverName(), ['pgsql', 'mysql'])) {
            defined('CRAFT_DB_DRIVER') || define('CRAFT_DB_DRIVER', DB::connection()->getDriverName());
        }

        $this->app->singleton('Craft', function() {
            /**
             * When developing or when running tests, the working directory
             * will be different, and the yii-adapter will need to look
             * in a different location for the bootstrap files.
             */
            $basePath = match (true) {
                file_exists(getcwd() . '/bootstrap/console.php') => getcwd(),
                file_exists(base_path() . '/vendor/craftcms/cms/bootstrap/console.php') => base_path() . '/vendor/craftcms/cms',
                file_exists(getcwd() . '/vendor/craftcms/cms/bootstrap/console.php') => getcwd() . '/vendor/craftcms/cms',
                default => throw new Exception("Bootstrap files could not be found.")
            };

            if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
                $app = require $basePath . '/bootstrap/console.php';
            } else {
                /**
                 * Yii seems weird about these
                 */
                $_SERVER = array_merge($_SERVER, [
                    'SCRIPT_FILENAME' => public_path('index.php'),
                    'SCRIPT_NAME' => '/index.php',
                ]);

                $app = require $basePath . '/bootstrap/web.php';
            }

            $this->bootEvents();

            return $app;
        });
    }

    public function boot(): void
    {
        /**
         * Register the base aliases that Yii sets, this has to be after
         * the constants as composer will autoload the BaseYii class.
         */
        Aliases::set('@app', base_path());

        foreach (BaseYii::$aliases as $alias => $path) {
            Aliases::set($alias, $path);
        }

        /**
         * When running in a Craft 5 upgraded project, the User model
         * won't exist. As such we need to use the base model.
         */
        if (!class_exists(config('auth.providers.users.model')) || !Config::get('auth.providers.users.model') instanceof User) {
            Config::set('auth.providers.users.model', User::class);
        }

        /**
         * In a Craft 5 upgraded project, the namespace won't be
         * detected automatically, we set it to "App" here.
         */
        try {
            $this->app->getNamespace();
        } catch (\RuntimeException) {
            $reflectionClass = new \ReflectionClass($this->app);
            $reflectionProperty = $reflectionClass->getProperty('namespace');
            $reflectionProperty->setValue($this->app, 'App');
        }

        /**
         * Prefix is not generally a configuration variable that
         * is set through the environment in Laravel, so
         * we set it here for backwards compatibility.
         */
        $connection = Config::get('database.default');
        Config::set("database.connections.{$connection}.prefix", Env::get('DB_TABLE_PREFIX'));

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
                $signature .= $this->convertDefinition($definition);
            }

            foreach ($command['definition']['options'] as $definition) {
                $signature .= $this->convertDefinition($definition);
            }

            ConsoleApplication::starting(function(ConsoleApplication $artisan) use ($app, $command, $signature) {
                $artisanName = explode(' ', $signature)[0];

                if ($artisan->has("craft:{$artisanName}")) {
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

    public function convertDefinition(array $definition): string
    {
        if ($definition['name'] === '--help') {
            return '';
        }

        $definitionSignature = $definition['name'];

        if (!$definition['default'] && !($definition['required'] ?? true)) {
            $definitionSignature .= '?';
        }

        if ($definition['default']) {
            if (is_array($definition['default'])) {
                $definition['default'] = implode(',', $definition['default']);
            }

            $definitionSignature .= "={$definition['default']}";
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

        /**
         * Utilities
         */
        AssetIndexes::registerEvents();
        ClearCaches::registerEvents();
    }
}
