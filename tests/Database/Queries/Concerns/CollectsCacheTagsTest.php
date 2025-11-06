<?php

use craft\elements\Entry as EntryElement;
use CraftCms\Cms\Database\Queries\Events\DefineCacheTags;
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
