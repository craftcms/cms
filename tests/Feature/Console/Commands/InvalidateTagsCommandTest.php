<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\artisan;

it('clears the caches tagged with the tag and fires ElementCachesInvalidated', function () {
    Event::fake([ElementCachesInvalidated::class]);
    DependencyCache::put('tagged', 'value', null, new TagDependency(['template']));
    DependencyCache::put('untagged', 'value', null, new TagDependency(['other']));

    artisan('craft:invalidate-tags:template')->assertSuccessful();

    expect(DependencyCache::get('tagged'))->toBeNull()
        ->and(DependencyCache::get('untagged'))->toBe('value');
    Event::assertDispatched(fn (ElementCachesInvalidated $event) => $event->tags === ['template']);
});

it('invalidates every tag option with all', function () {
    Event::fake([ElementCachesInvalidated::class]);

    artisan('craft:invalidate-tags:all')->assertSuccessful();

    $tags = array_column(ClearCaches::tagOptions(), 'tag');
    expect($tags)->toContain('template');
    Event::assertDispatchedTimes(ElementCachesInvalidated::class, count($tags));

    foreach ($tags as $tag) {
        Event::assertDispatched(fn (ElementCachesInvalidated $event) => $event->tags === [$tag]);
    }
});
