<?php

namespace CraftCms\Cms\Providers;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use CraftCms\Cms\Support\Env;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/** @since 6.0.0 */
final class AppServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    public function register(): void {}

    public function boot(): void
    {
        Aliases::set('@root', Env::get('CRAFT_ROOT_PATH', $this->app->basePath()));
        Aliases::set('@craftcms', FileHelper::normalizePath($this->root.'/../'));
        Aliases::set('@packageRoot', '@craftcms/cms');
        Aliases::set('@package', '@packageRoot/src');

        if ($webUrl = Env::get('CRAFT_WEB_URL')) {
            Aliases::set('@web', $webUrl);
        }

        AboutCommand::add('Craft CMS', fn () => [
            'Edition' => Craft::$app->edition->name,
            'Schema' => Craft::$app->schemaVersion,
            'Version' => Craft::$app->getVersion(),
        ]);

        $this->bootMiddleware();

        $this->loadRoutesFrom("{$this->root}/routes/routes.php");
        $this->loadViewsFrom("{$this->root}/resources/views", 'craftcms');

        if (! $this->app->runningInConsole()) {
            return;
        }

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
            SendPoweredByHeader::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        collect([
            RequireCpRequest::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));
    }
}
