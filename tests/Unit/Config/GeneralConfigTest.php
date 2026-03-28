<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Support\Facades\Config;

it('can get from container', function () {
    expect(app(GeneralConfig::class))->toBe(Config::get('craft.general'));
    expect(app(GeneralConfig::class))->toBe(Cms::config());
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
    app(ConfigServiceProvider::class, ['app' => app()])->boot();

    expect(Cms::config()->cpTrigger)->toBe('adminus');
});

it('can set queueName via fluent setter', function () {
    $config = GeneralConfig::create()->queueName('custom');

    expect($config->queueName)->toBe('custom');
});

it('can set lowPriorityQueueName via fluent setter', function () {
    $config = GeneralConfig::create()->lowPriorityQueueName('custom');

    expect($config->lowPriorityQueueName)->toBe('custom');
});

it('can set trackedQueueNames via fluent setter', function () {
    $config = GeneralConfig::create()->trackedQueueNames(['craft', 'default']);

    expect($config->trackedQueueNames)->toBe(['craft', 'default']);
});

it('does not expose moved deprecated members on the new class', function () {
    $config = GeneralConfig::create();

    expect(method_exists($config, 'devMode'))->toBeFalse()
        ->and(method_exists($config, 'enableCsrfProtection'))->toBeFalse()
        ->and(method_exists($config, 'securityKey'))->toBeFalse()
        ->and(property_exists($config, 'allowedGraphqlOrigins'))->toBeFalse()
        ->and(property_exists($config, 'userSessionDuration'))->toBeFalse()
        ->and(method_exists($config, 'pageTrigger'))->toBeTrue()
        ->and(property_exists($config, 'pageTrigger'))->toBeTrue();
});

it('normalizes pageTrigger on the main config class', function () {
    $config = GeneralConfig::create();

    expect($config->pageTrigger('page')->getPageTrigger())->toBe('?page=')
        ->and($config->getPageTriggerParam())->toBe('page')
        ->and($config->pageTrigger('?page')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('?page=')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('p')->getPageTrigger())->toBe('?p=')
        ->and($config->pageTrigger('?p=')->getPageTrigger())->toBe('?p=')
        ->and($config->pageTrigger('page/')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('')->getPageTrigger())->toBe('?p=');
});
