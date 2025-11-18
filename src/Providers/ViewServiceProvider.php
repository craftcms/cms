<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Twig\Engine;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    private string $root = __DIR__.'/../..';

    #[\Override]
    public function register(): void
    {
        $this->loadViewsFrom("{$this->root}/resources/templates", 'craftcms');
        $this->loadViewsFrom("{$this->root}/resources/views", 'c');

        $this->app->make(ViewFactory::class)->addExtension(
            'twig',
            'twig',
            fn () => $this->app->make(Engine::class)
        );

        Vite::useHotFile("{$this->root}/resources/hot");
    }
}
