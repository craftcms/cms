<?php

namespace CraftCms\Cms\Twig;

use Illuminate\Support\ServiceProvider;

final class TwigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(dirname(__DIR__, 3).'/src/templates', 'craftcms');

        $this->app['view']->addExtension(
            'twig',
            'twig',
            fn () => $this->app->make(Engine::class)
        );
    }
}
