<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ServiceProvider;

final class TwigServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->loadViewsFrom(dirname(__DIR__, 2).'/resources/templates', 'craftcms');

        $this->app->make(ViewFactory::class)->addExtension(
            'twig',
            'twig',
            fn () => $this->app->make(Engine::class)
        );
    }
}
