<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;

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

it('fails when the cache store fails', function () {
    failCacheStore();

    artisan('craft:invalidate-tags:template')->assertFailed();
    artisan('craft:invalidate-tags:all')->assertFailed();
});

function failCacheStore(): void
{
    Exceptions::fake();
    // Only invalidation fails; everything else still reaches the real store.
    $store = Mockery::mock(DependencyCache::store())->makePartial();
    $store->shouldReceive('deleteMultiple')->andThrow(new RuntimeException('Cache store unavailable'));
    DependencyCache::partialMock()->shouldReceive('store')->andReturn($store);
}
