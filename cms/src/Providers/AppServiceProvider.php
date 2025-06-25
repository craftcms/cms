<?php

namespace Craft\Cms\Providers;

use Craft\Aliases\Facades\Aliases;
use Craft\Cms\Http\Middleware\ExtractNamespace;
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
