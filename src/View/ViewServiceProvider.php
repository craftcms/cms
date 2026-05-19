<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\View\Events\ViewAssetsRendering;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

    public function boot(TemplateHooks $hooks): void
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

        $hooks->register('cp.layouts.elementindex', PrepareElementIndexVariables::class);
        $hooks->register('cp.elements.toolbar', PrepareElementToolbarVariables::class);
        $hooks->register('cp.elements.sources', PrepareElementSourcesVariables::class);
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
}
