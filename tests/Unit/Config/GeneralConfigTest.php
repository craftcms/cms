<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Config\QueueConfig;
use CraftCms\Cms\Config\TemplatesConfig;
use Illuminate\Support\Facades\Config;

it('can get from container', function () {
    expect(app(GeneralConfig::class))->toBe(Config::get('craft.general'));
    expect(app(GeneralConfig::class))->toBe(Cms::config());
});

it('can set the tracked queue names via fluent setter', function () {
    $config = GeneralConfig::create()->queue(QueueConfig::create()->trackedNames(['craft', 'default']));

    expect($config->queue->trackedNames)->toBe(['craft', 'default']);
});

it('can set the compiled templates path via fluent setter', function () {
    $config = GeneralConfig::create()->templates(TemplatesConfig::create()->compiledPath('@storage/custom-compiled-templates'));

    expect($config->templates->compiledPath)->toBe('@storage/custom-compiled-templates');
});

it('normalizes storage disk configuration', function () {
    $assets = GeneralConfig::create()->assets([
        'tempUploadDisk' => 'asset-uploads',
        'uploadSessionDisk' => 'upload-sessions',
    ])->assets;

    expect($assets->getTempUploadDisk())->toBe('asset-uploads')
        ->and($assets->getUploadSessionDisk())->toBe('upload-sessions')
        ->and($assets->getTrashDisk())->toBeNull();
});

it('automatically eager loads elements by default', function () {
    $config = GeneralConfig::create();

    expect($config->autoEagerLoadElements)->toBeTrue()
        ->and($config->autoEagerLoadElements(false)->autoEagerLoadElements)->toBeFalse();
});

it('normalizes activity retention durations and rejects negative values', function () {
    $config = GeneralConfig::create();

    expect($config->activityRetentionDuration)->toBe(0)
        ->and($config->activityRetentionDuration('P1D')->activityRetentionDuration)->toBe(86400)
        ->and(fn () => $config->activityRetentionDuration(-1))->toThrow(InvalidArgumentException::class);
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
