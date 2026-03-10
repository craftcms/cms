<?php

namespace CraftCms\Yii2Adapter\Tests;

use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\SocialiteServiceProvider as LaravelSocialiteServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(LaravelSocialiteServiceProvider::class) && ! app()->bound(SocialiteFactory::class)) {
            app()->register(LaravelSocialiteServiceProvider::class);
        }
    }
}
