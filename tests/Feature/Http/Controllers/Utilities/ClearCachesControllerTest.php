<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

afterEach(function () {
    ClearCaches::flushState();
});

test('unauthorized users cannot access cache clearing utility', function () {
    Cms::config()->disabledUtilities = [ClearCaches::id()];

    postJson(action([ClearCachesController::class, 'clearCaches']), [
        'caches' => 'all',
    ])
        ->assertForbidden();
});

test('can clear specific caches', function () {
    ClearCaches::add('first', [
        'label' => 'First cache',
        'action' => fn () => Cache::forget('first-cache'),
    ]);
    ClearCaches::add('second', [
        'label' => 'Second cache',
        'action' => fn () => Cache::forget('second-cache'),
    ]);
    Cache::put('first-cache', 'value');
    Cache::put('second-cache', 'value');

    postJson(action([ClearCachesController::class, 'clearCaches']), [
        'caches' => ['first'],
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Caches cleared.');

    expect(Cache::has('first-cache'))->toBeFalse()
        ->and(Cache::has('second-cache'))->toBeTrue();
});

test('sends the utility’s form back where it came from', function () {
    ClearCaches::add('first', [
        'label' => 'First cache',
        'action' => fn () => Cache::forget('first-cache'),
    ]);

    post(action([ClearCachesController::class, 'clearCaches']), [
        'caches' => ['first'],
    ])
        ->assertRedirectBack();
});

test('requires caches parameter', function () {
    postJson(action([ClearCachesController::class, 'clearCaches']), [])
        ->assertJsonValidationErrors(['caches']);
});

test('can invalidate cache tags', function () {
    Event::fake([ElementCachesInvalidated::class]);

    postJson(action([ClearCachesController::class, 'invalidateTags']), [
        'tags' => ['test-tag-1', 'test-tag-2'],
    ])
        ->assertRedirectBack();

    Event::assertDispatched(fn (ElementCachesInvalidated $event) => $event->tags === ['test-tag-1', 'test-tag-2']);
});

test('reports a failure when the cache store fails to invalidate tags', function () {
    Exceptions::fake();
    // Only invalidation fails; everything else still reaches the real store.
    $store = Mockery::mock(DependencyCache::store())->makePartial();
    $store->shouldReceive('deleteMultiple')->andThrow(new RuntimeException('Cache store unavailable'));
    DependencyCache::partialMock()->shouldReceive('store')->andReturn($store);

    postJson(action([ClearCachesController::class, 'invalidateTags']), [
        'tags' => ['test-tag-1'],
    ])->assertBadRequest();
});
