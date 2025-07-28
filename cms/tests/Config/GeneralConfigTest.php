<?php

use CraftCms\Cms\Config\GeneralConfig;

it('can get from container', function () {
    expect(app(GeneralConfig::class))->toBe(Config::get('craft.general'));
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
