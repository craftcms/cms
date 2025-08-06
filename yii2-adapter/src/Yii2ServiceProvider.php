<?php

namespace CraftCms\Yii2Adapter;

use craft\console\controllers\HelpController;
use craft\helpers\App;
use craft\services\Utilities;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use yii\BaseYii;

class Yii2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConstants();
        $this->registerLegacyApp();

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->app
            ->setBasePath(CRAFT_BASE_PATH)
            ->useStoragePath(CRAFT_STORAGE_PATH)
            ->useEnvironmentPath(CRAFT_DOTENV_PATH);

        if ($this->inLaravelSkeleton()) {
            defined('CRAFT_CONFIG_PATH') || define('CRAFT_CONFIG_PATH', config_path('craft'));
            defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', lang_path());
            defined('CRAFT_LICENSE_KEY_PATH') || define('CRAFT_LICENSE_KEY_PATH', config_path('craft'));
        } else {
            defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', base_path('translations'));
            defined('CRAFT_LICENSE_KEY_PATH') || define('CRAFT_LICENSE_KEY_PATH', config_path());

            /**
             * Configure the Laravel application to look into
             * folders defined by the Craft CMS constants.
             */
            $this->app
                // When not in a Laravel skeleton, we don't want to conflict any config files.
                ->useConfigPath(base_path('config/laravel'))
                ->useLangPath(CRAFT_TRANSLATIONS_PATH)
                ->usePublicPath(Env::get('CRAFT_WEB_ROOT', $this->app->publicPath()));
        }

        $this->setLaravelDefaults();
    }

    protected function inLaravelSkeleton(): bool
    {
        return is_dir(config_path('craft')) || file_exists(config_path('auth.php'));
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

        if (is_dir(resource_path('views'))) {
            defined('CRAFT_TEMPLATES_PATH') || define('CRAFT_TEMPLATES_PATH', resource_path('views'));
        } else {
            defined('CRAFT_TEMPLATES_PATH') || define('CRAFT_TEMPLATES_PATH', base_path('templates'));
        }

        defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', base_path());
        defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', base_path('vendor'));
        defined('CRAFT_STORAGE_PATH') || define('CRAFT_STORAGE_PATH', storage_path());
        defined('CRAFT_DOTENV_PATH') || define('CRAFT_DOTENV_PATH', base_path());
    }

    protected function registerLegacyApp(): void
    {
        $this->app->singleton('Craft', function() {
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
            }

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

    public function boot(GeneralConfig $generalConfig): void
    {
        $this->ensureStorageFoldersExist($generalConfig);

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

    private function ensureStorageFoldersExist(GeneralConfig $generalConfig): void
    {
        $dirMode = $generalConfig->defaultDirMode ?? 0775;

        File::ensureDirectoryExists($this->app->storagePath(), $dirMode);
        File::ensureDirectoryExists($this->app->storagePath('framework/cache'), $dirMode);
        File::ensureDirectoryExists($this->app->storagePath('framework/views'), $dirMode);
        File::ensureDirectoryExists($this->app->storagePath('framework/sessions'), $dirMode);
        File::ensureDirectoryExists($this->app->storagePath('runtime'), $dirMode);

        if (!App::isStreamLog()) {
            File::ensureDirectoryExists($this->app->storagePath('logs'), $dirMode);
        }
    }
}
