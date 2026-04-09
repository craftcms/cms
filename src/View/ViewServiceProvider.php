<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
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

        Vite::useHotFile("{$this->root}/resources/hot");
    }

    public function boot(TemplateHooks $hooks): void
    {
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

        $this->app->booted(function () {
            /**
             * This ensures that when Laravel tries to find an error view,
             * it will look in the CP templates for it as well.
             */
            if (request()->isCpRequest()) {
                config()->set('view.paths', array_merge(
                    config('view.paths'),
                    [dirname(__DIR__, 2).'/resources/templates']
                ));
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
        $this->app->booted(function () {
            /** @var Factory $factory */
            $factory = $this->app->make(ViewFactory::class);

            /**
             * Prepend the Craft CMS Control panel views when
             * we're in CP Template mode. This makes view()
             * work without a 'craftcms::' prefix.
             */
            if (TemplateMode::is(TemplateMode::Cp)) {
                $factory->prependLocation("{$this->root}/resources/templates");
                $factory->prependLocation("{$this->root}/resources/views");
            }

            foreach (TemplateMode::get()->templateRoots() as $namespace => $roots) {
                $factory->addNamespace($namespace, $roots);

                foreach ($roots as $root) {
                    $factory->prependLocation($root);
                }
            }
        });
    }
}
