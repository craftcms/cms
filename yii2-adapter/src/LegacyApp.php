<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Craft;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Yii2Adapter\Event\EventCompatibility;
use CraftCms\Yii2Adapter\Http\Controller;
use Illuminate\Contracts\Foundation\Application;
use yii\base\Module;
use yii\BaseYii;

readonly class LegacyApp
{
    public function register(Application $app): void
    {
        $app->singleton('Craft', function() use ($app) {
            $laravelApp = $app;

            /**
             * Register the base aliases that Yii sets, this has to be after
             * the constants as composer will autoload the BaseYii class.
             */
            Aliases::set('@app', base_path());

            foreach (BaseYii::$aliases as $alias => $path) {
                Aliases::set($alias, $path);
            }

            if ($app->runningInConsole() && !$app->runningUnitTests()) {
                $craftApp = require __DIR__ . '/../bootstrap/console.php';
            } else {
                /**
                 * Yii seems weird about these
                 */
                $_SERVER = array_merge($_SERVER, [
                    'SCRIPT_FILENAME' => $app->publicPath('index.php'),
                    'SCRIPT_NAME' => '/index.php',
                ]);

                $craftApp = require __DIR__ . '/../bootstrap/web.php';

                if (!$craftApp->controller) {
                    $controller = new Controller('', $craftApp);

                    $craftApp->controller = $controller;
                }
            }

            /** @var \craft\web\Application|\craft\console\Application $craftApp */
            $craftApp->setTimeZone(Cms::timezone());
            $craftApp->language = app()->getLocale();

            Craft::$app = $craftApp;
            Craft::populateCustomFieldBehavior();

            /**
             * Every legacy class that fires Yii events should listen to
             * the relevant Laravel event and trigger the Yii event.
             */
            new EventCompatibility()->boot();

            foreach ($laravelApp->make(Plugins::class)->getAllPlugins() as $plugin) {
                if ($plugin instanceof Module) {
                    Craft::$app->setModule($plugin->handle, $plugin);
                }
            }

            /**
             * Globals, Categories, Tags
             */
            new DeprecatedConcepts()->boot();

            DeprecatedConcepts::bootYiiEvents();

            return $app;
        });
    }
}
