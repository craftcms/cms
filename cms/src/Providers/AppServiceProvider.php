<?php

namespace Craft\Cms\Providers;

use Craft\Aliases\Facades\Aliases;
use Craft\Cms\Http\Middleware\ExtractNamespace;
use Craft\Cms\Support\Str;
use craft\helpers\FileHelper;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    private array $configFiles = [
        'general',
    ];

    public function register(): void
    {
        Aliases::set('@package', FileHelper::normalizePath($this->root.'/src'));

        $this->loadRoutesFrom("{$this->root}/routes/web.php");
        $this->loadViewsFrom("{$this->root}/resources/views", 'craftcms');

        collect($this->configFiles)->each(function (string $file) {
            $this->mergeConfigFrom("{$this->root}/config/$file.php", 'craftcms');
        });

        /**
         * Craft relies on Str::random() to generate slugs and other
         * special-character-sensitive strings. Laravel by default
         * uses any character which causes issues.
         */
        Str::createRandomStringsUsing(function (int $length) {
            $validChars = 'abcdefghijklmnopqrstuvwxyz';
            $randomString = '';

            // count the number of chars in the valid chars string so we know how many choices we have
            $numValidChars = mb_strlen($validChars);

            // repeat the steps until we've created a string of the right length
            for ($i = 0; $i < $length; $i++) {
                // pick a random number from 1 up to the number of valid chars
                $randomPick = random_int(0, $numValidChars - 1);

                // take the random character out of the string of valid chars
                $randomChar = $validChars[$randomPick];

                // add the randomly-chosen char onto the end of our string
                $randomString .= $randomChar;
            }

            return $randomString;
        });
    }

    public function boot(): void
    {
        $this->bootMiddleware();

        if (! $this->app->runningInConsole()) {
            return;
        }

        collect($this->configFiles)->each(function ($file) {
            $this->publishes(["{$this->root}/config/$file.php" => config_path("craft/$file.php")], 'craftcms');
        });

        $this->publishes([
            "{$this->root}/resources/views" => resource_path('views/vendor/craftcms'),
        ], 'craftcms-views');

        $this->publishes([
            base_path('vendor/craftcms/cms/cpresources') => public_path('cpresources'),
        ], 'craftcms-cpresources');
    }

    protected function bootMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        collect([
            ExtractNamespace::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft', $middleware));
    }
}
