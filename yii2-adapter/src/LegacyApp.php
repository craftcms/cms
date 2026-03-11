<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Craft;
use CraftCms\Aliases\Aliases;
use CraftCms\Yii2Adapter\Event\EventCompatibility;
use CraftCms\Yii2Adapter\Http\Controller;
use Illuminate\Contracts\Foundation\Application;
use yii\BaseYii;

final readonly class LegacyApp
{
    public function register(Application $app): void
    {
        $app->singleton('Craft', function() {
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

            /**
             * Every legacy class that fires Yii events should listen to
             * the relevant Laravel event and trigger the Yii event.
             */
            new EventCompatibility()->boot();

            /**
             * Globals, Categories, Tags
             */
            new DeprecatedConcepts()->boot();

            return $app;
        });
    }
}
