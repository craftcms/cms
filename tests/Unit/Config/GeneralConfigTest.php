<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GarbageCollectionConfig;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Config\GqlConfig;
use CraftCms\Cms\Config\QueueConfig;
use CraftCms\Cms\Config\RoutingConfig;
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
    $config = GarbageCollectionConfig::create();

    expect($config->activityRetentionDuration)->toBe(0)
        ->and($config->activityRetentionDuration('P1D')->activityRetentionDuration)->toBe(86400)
        ->and(fn () => $config->activityRetentionDuration(-1))->toThrow(InvalidArgumentException::class);
});

it('normalizes pageTrigger', function () {
    $config = RoutingConfig::create();

    expect($config->pageTrigger('page')->getPageTrigger())->toBe('?page=')
        ->and($config->getPageTriggerParam())->toBe('page')
        ->and($config->pageTrigger('?page')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('?page=')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('p')->getPageTrigger())->toBe('?p=')
        ->and($config->pageTrigger('?p=')->getPageTrigger())->toBe('?p=')
        ->and($config->pageTrigger('page/')->getPageTrigger())->toBe('?page=')
        ->and($config->pageTrigger('')->getPageTrigger())->toBe('?page=');
});

it('doesn’t share its config objects with clones', function () {
    $config = GeneralConfig::create();
    $clone = clone $config;
    $clone->gql->enabled(false);
    $clone->enableGql = false;

    expect($config->gql->enabled)->toBeTrue()
        ->and($clone->gql)->not->toBe($config->gql);
});

it('merges config objects with the settings already set', function () {
    $config = GeneralConfig::create()
        ->enableGql(false)
        ->gql(GqlConfig::create()->maxDepth(5))
        ->gql(['maxResults' => 10])
        ->maxGraphqlComplexity(100);

    expect($config->gql->enabled)->toBeFalse()
        ->and($config->gql->maxDepth)->toBe(5)
        ->and($config->gql->maxResults)->toBe(10)
        ->and($config->gql->maxComplexity)->toBe(100);
});
