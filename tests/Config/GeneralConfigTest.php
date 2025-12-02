<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Support\Facades\Config;

it('can get from container', function () {
    expect(resolve(GeneralConfig::class))->toBe(Config::get('craft.general'));
    expect(resolve(GeneralConfig::class))->toBe(Cms::config());
});

it('can get renamed settings', function () {
    $config = GeneralConfig::create();

    $config->aliases([
        '@webroot' => public_path(),
    ]);

    expect($config->environmentVariables)->toBe($config->aliases);
});

it('can set renamed settings', function () {
    $config = GeneralConfig::create();

    $config->environmentVariables = [
        '@webroot' => public_path(),
    ];

    expect($config->aliases)->toBe([
        '@webroot' => public_path(),
    ]);
});

test('env overrides get precedence over config', function () {
    putenv('CRAFT_CP_TRIGGER=adminus');

    // Simulate the application being loaded
    resolve(ConfigServiceProvider::class, ['app' => app()])->boot();

    expect(Cms::config()->cpTrigger)->toBe('adminus');
});
