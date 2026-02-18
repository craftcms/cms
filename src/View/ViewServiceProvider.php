<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
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

        $hooks->register('cp.layouts.elementindex', PrepareElementIndexVariables::class);
        $hooks->register('cp.elements.toolbar', PrepareElementToolbarVariables::class);
        $hooks->register('cp.elements.sources', PrepareElementSourcesVariables::class);
    }
}
