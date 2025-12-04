<?php

use CraftCms\Cms\Database\Queries\Events\DefineCacheTags;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use Illuminate\Support\Facades\Event;

it('gathers cache tags used after a query was executed', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    $entry = Entry::factory()->create();

    entryQuery()->id($entry->id)->all();

    /** @var \CraftCms\DependencyAwareCache\Dependency\TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.$entry->id);
});

it('only adds ids when less than 100 ids have been requested', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    entryQuery()->id(range(1, 100))->all();

    /** @var \CraftCms\DependencyAwareCache\Dependency\TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::1');
    expect($dependency->tags)->toContain('element::100');

    Craft::$app->getElements()->startCollectingCacheInfo();

    entryQuery()->id(range(1, 101))->all();

    /** @var \CraftCms\DependencyAwareCache\Dependency\TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->not()->toContain('element::1');
    expect($dependency->tags)->not()->toContain('element::100');
});

it('can define extra cache tags', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    Event::listen(DefineCacheTags::class, function (DefineCacheTags $event) {
        $event->tags[] = 'foo';
    });

    entryQuery()->all();

    /** @var \CraftCms\DependencyAwareCache\Dependency\TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain(sprintf(
        'element::%s::%s',
        EntryElement::class,
        'foo',
    ));
});
