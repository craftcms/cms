<?php

namespace CraftCms\Cms\Providers;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Middleware\ExtractNamespace;
use CraftCms\Cms\Http\Middleware\FlushProjectConfig;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use CraftCms\Cms\Plugin\Commands\InstallCommand;
use CraftCms\Cms\Support\Env;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/** @since 6.0.0 */
final class AppServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    public function register(): void
    {
        /**
         * HandleActionRequest is special and needs to run
         * before any other middleware as it rewrites
         * which path needs to get used.
         */
        $kernel = $this->app->get(HttpKernel::class);
        $kernel->setGlobalMiddleware(array_merge([
            HandleActionRequest::class,
        ], $kernel->getGlobalMiddleware()));

        Authenticate::redirectUsing(fn () => app(GeneralConfig::class)->loginPath);
    }

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

        $this->commands([
            InstallCommand::class,
        ]);

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
            FlushProjectConfig::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft', $middleware));

        collect([
            RequireCpRequest::class,
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.cp', $middleware));

        collect([
            'web'
        ])->each(fn ($middleware) => $router->pushMiddlewareToGroup('craft.web', $middleware));
    }
}
