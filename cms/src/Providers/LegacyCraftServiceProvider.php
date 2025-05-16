<?php

namespace Craft\Cms\Providers;

use Craft;
use Craft\Cms\Console\CraftCommand;
use craft\console\controllers\HelpController;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Yii;

class LegacyCraftServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (defined('CRAFT_TEMPLATES_PATH')) {
            return;
        }

        /*
         * This is to prevent Yii from running exit(), we want to catch Yii
         * exiting when for example a redirect is executed.
         */
        define('YII_ENV_TEST', true);

        /**
         * Set some base CRAFT variables to their Laravel equivalents.
         */
        define('CRAFT_TEMPLATES_PATH', resource_path('views'));
        define('CRAFT_BASE_PATH', base_path());
        define('CRAFT_VENDOR_PATH', CRAFT_BASE_PATH . '/vendor');
        define('CRAFT_CONFIG_PATH', config_path('craft'));

        $this->app->singleton('Craft', function() {
            if ($this->app->runningInConsole()) {
                /** @var \craft\console\Application $app */
                $app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
            } else {
                /**
                 * Yii seems weird about these
                 */
                $_SERVER = array_merge($_SERVER, [
                    'SCRIPT_FILENAME' => public_path('index.php'),
                    'SCRIPT_NAME' => '/index.php',
                ]);

                /** @var \craft\web\Application $app */
                $app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
            }

            return $app;
        });
    }

    public function boot(): void
    {
        $this->bootLegacyCommands();
    }

    private function bootLegacyCommands(): void
    {
        /** @var \craft\console\Application $app */
        $app = $this->app->get('Craft');
        $controller = new HelpController('help', $app);
        $commands = $controller->allCommandsInfo();

        foreach ($commands as $command) {
            $signature = str_replace('/', ':', $command['name']);

            foreach ($command['definition']['arguments'] as $argument) {
                $argumentSignature = $argument['name'];

                if (!$argument['default'] && !$argument['required']) {
                    $argumentSignature .= '?';
                }

                if ($argument['default']) {
                    $argumentSignature .= "={$argument['default']}";
                }

                if ($argument['description']) {
                    $argumentSignature .= " : {$argument['description']}";
                }

                $signature .= " {{$argumentSignature}}";
            }

            foreach ($command['definition']['options'] as $option) {
                if ($option['name'] === '--help') {
                    continue;
                }

                $name = Str::after($option['name'], '--');
                $optionSignature = "--{$name}";

                if ($option['default']) {
                    if (is_array($option['default'])) {
                        $option['default'] = implode(',', $option['default']);
                    }

                    $optionSignature .= "={$option['default']}";
                }

                if ($option['description']) {
                    $optionSignature .= " : {$option['description']}";
                }

                $signature .= " {{$optionSignature}}";
            }

            ConsoleApplication::starting(function(ConsoleApplication $artisan) use ($app, $command, $signature) {
                if ($artisan->has("craft:{$signature}")) {
                    return;
                }

                $artisan->add(new CraftCommand($app, "craft:{$signature}", $command['description']));
            });
        }
    }
}
