<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
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
        ->assertRedirectBack();

    expect(Cache::has('first-cache'))->toBeFalse()
        ->and(Cache::has('second-cache'))->toBeTrue();
});

test('requires caches parameter', function () {
    postJson(action([ClearCachesController::class, 'clearCaches']), [])
        ->assertJsonValidationErrors(['caches']);
});

test('can invalidate cache tags', function () {
    postJson(action([ClearCachesController::class, 'invalidateTags']), [
        'tags' => ['test-tag-1', 'test-tag-2'],
    ])
        ->assertRedirectBack();
});
