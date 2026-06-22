<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\View\BladeDirectives\AuthDirective;
use CraftCms\Cms\View\BladeDirectives\CacheDirective;
use CraftCms\Cms\View\BladeDirectives\HookDirective;
use CraftCms\Cms\View\BladeDirectives\NamespaceDirective;
use CraftCms\Cms\View\BladeDirectives\PageLifecycleDirective;
use CraftCms\Cms\View\BladeDirectives\PaginationDirective;
use CraftCms\Cms\View\BladeDirectives\ResourceDirective;
use CraftCms\Cms\View\BladeDirectives\ResponseDirective;
use CraftCms\Cms\View\Events\ViewAssetsRendering;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Factory;
use Override;

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
        $this->app->booted(function () {
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
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $this->registerBladeDirectivesWith($blade);
        });

        if ($this->app->resolved('blade.compiler')) {
            $this->registerBladeDirectivesWith($this->app->make(CompilerInterface::class));
        }
    }

    private function registerBladeDirectivesWith(BladeCompiler $blade): void
    {
        PageLifecycleDirective::register($blade);
        ResourceDirective::register($blade);
        CacheDirective::register($blade);
        HookDirective::register($blade);
        NamespaceDirective::register($blade);
        PaginationDirective::register($blade);
        AuthDirective::register($blade);
        ResponseDirective::register($blade);
    }
}
