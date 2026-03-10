<?php

namespace CraftCms\Yii2Adapter\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\SocialiteServiceProvider as LaravelSocialiteServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

class TestCase extends Orchestra
{
    use WithWorkbench;

    #[Override]
    protected function getPackageProviders($app): array
    {
        return array_values(array_unique([
            ...parent::getPackageProviders($app),
            LaravelSocialiteServiceProvider::class,
        ]));
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        if (! $this->app->bound('config')) {
            $this->app->instance('config', new ConfigRepository([
                'craft.general.socialiteProviders' => [],
                'services' => [],
            ]));
        }

        if (! $this->app->bound(SocialiteFactory::class)) {
            $this->app->singleton(SocialiteFactory::class, fn ($app) => new SocialiteManager($app));
        }
    }
}
