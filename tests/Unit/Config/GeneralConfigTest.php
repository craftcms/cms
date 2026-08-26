<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Support\Facades\Config;

it('can get from container', function () {
    expect(app(GeneralConfig::class))->toBe(Config::get('craft.general'));
    expect(app(GeneralConfig::class))->toBe(Cms::config());
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

it('can set compiledTemplatesPath via fluent setter', function () {
    $config = GeneralConfig::create()->compiledTemplatesPath('@storage/custom-compiled-templates');

    expect($config->compiledTemplatesPath)->toBe('@storage/custom-compiled-templates');
});

it('requires a default Asset Transformer', function () {
    $config = GeneralConfig::create();

    expect($config->defaultAssetTransformer)->toBe('craft')
        ->and($config->defaultAssetTransformer('remote')->defaultAssetTransformer)->toBe('remote')
        ->and(fn () => $config->defaultAssetTransformer(''))->toThrow(RuntimeException::class);
});

it('sets the transform generation policy', function () {
    $config = GeneralConfig::create();

    expect($config->generateTransformsBeforePageLoad)->toBeFalse()
        ->and($config->generateTransformsBeforePageLoad()->generateTransformsBeforePageLoad)->toBeTrue();
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
        ->and($config->pageTrigger('')->getPageTrigger())->toBe('?page=');
});
