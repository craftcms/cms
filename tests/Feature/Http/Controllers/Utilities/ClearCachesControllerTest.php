<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\ClearCachesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\ClearCaches;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access cache clearing utility', function () {
    Cms::config()->disabledUtilities = [ClearCaches::id()];

    postJson(action([ClearCachesController::class, 'clearCaches']), [
        'caches' => 'all',
    ])
        ->assertForbidden();
});

test('can clear specific caches', function () {
    postJson(action([ClearCachesController::class, 'clearCaches']), [
        'caches' => 'all',
    ])
        ->assertRedirectBack();
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
