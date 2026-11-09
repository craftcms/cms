<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Blade\Directives\AuthDirective;
use CraftCms\Cms\Blade\Directives\CacheDirective;
use CraftCms\Cms\Blade\Directives\HookDirective;
use CraftCms\Cms\Blade\Directives\NamespaceDirective;
use CraftCms\Cms\Blade\Directives\PageLifecycleDirective;
use CraftCms\Cms\Blade\Directives\PaginationDirective;
use CraftCms\Cms\Blade\Directives\ResourceDirective;
use CraftCms\Cms\Blade\Directives\ResponseDirective;
use CraftCms\Cms\View\Events\ViewAssetsRendering;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Override;
use Throwable;

/**
 * @since 6.0.0
 */
class ViewServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    #[Override]
    public function register(): void
    {
        $this->loadViewsFrom("{$this->root}/resources/templates", 'craftcms');
        $this->loadViewsFrom("{$this->root}/resources/views", 'c');

        $this->app->make(ViewFactory::class)->addExtension(
            'twig',
            'twig',
            fn () => $this->app->make(TwigEngine::class)
        );
    }

    public function boot(): void
    {
        Event::listen(function (ViewAssetsRendering $event) {
            app(InternalAssetRegistry::class)->flush();
        });

        /**
         * Console should run in CP Template mode by default.
         */
        if ($this->app->runningInConsole()) {
            TemplateMode::set(TemplateMode::Cp);
        }

        $this->registerTemplateRoots();
        $this->registerTemplateGlobals();
        $this->registerBladeDirectives();
        $this->registerErrorPages();
    }

    private function registerErrorPages(): void
    {
        // Registered once every provider has booted, so other exception render callbacks run first.
        $this->app->booted(function (): void {
            $handler = $this->app->make(ExceptionHandler::class);

            if ($handler instanceof Handler) {
                $handler->renderable(fn (Throwable $e, Request $request) => $this->app->make(ErrorPages::class)->render($e, $request, $handler));
            }
        });
    }

    private function registerTemplateGlobals(): void
    {
        View::composer('*', function (\Illuminate\View\View $view): void {
            foreach (app(TemplateGlobals::class)->resolve() as $key => $value) {
                $view->with($key, $value);
            }
        });
    }

    private function registerTemplateRoots(): void
    {
        $this->app->afterBootstrapping(BootProviders::class, function () {
            /** @var Factory $factory */
            $factory = $this->app->make(ViewFactory::class);

            foreach (TemplateMode::get()->templateRoots() as $namespace => $roots) {
                $factory->addNamespace($namespace, $roots);

                foreach ($roots as $root) {
                    $factory->prependLocation($root);
                }
            }
        });
    }

    private function registerBladeDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            PageLifecycleDirective::register($blade);
            ResourceDirective::register($blade);
            CacheDirective::register($blade);
            HookDirective::register($blade);
            NamespaceDirective::register($blade);
            PaginationDirective::register($blade);
            AuthDirective::register($blade);
            ResponseDirective::register($blade);
        });
    }
}
