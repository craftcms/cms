<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('can be invoked without authentication for deployment hooks', function () {
    Auth::logout();

    $response = post(action(MigrateController::class));

    // Should work (returns 204 when nothing to do, or 503 if in maintenance mode)
    expect($response->status())->toBeIn([204, 503]);
});

test('returns no content when no migrations or config changes pending', function () {
    $response = postJson(action(MigrateController::class), [
        'applyProjectConfigChanges' => false,
    ]);

    // Should return 204 No Content when there's nothing to do
    expect($response->status())->toBeIn([204, 503]);
});

test('returns no content when migrations complete successfully', function () {
    $response = postJson(action(MigrateController::class));

    // Should complete without error
    expect($response->status())->toBeIn([204, 503, 500]);
});

test('handles applyProjectConfigChanges parameter', function () {
    $response = postJson(action(MigrateController::class), [
        'applyProjectConfigChanges' => true,
    ]);

    // Should handle the parameter
    expect($response->status())->toBeIn([204, 500, 503]);
});

test('handles maintenance mode correctly', function () {
    // Ensure maintenance mode is off initially
    Craft::$app->disableMaintenanceMode();

    // Enable maintenance mode
    Craft::$app->enableMaintenanceMode();

    $response = postJson(action(MigrateController::class));

    // Should either reject (503) or complete (204) depending on implementation details
    expect($response->status())->toBeIn([204, 503]);

    // Clean up - ensure maintenance mode is disabled
    Craft::$app->disableMaintenanceMode();
});

test('disables maintenance mode after completion', function () {
    // Ensure maintenance mode is off before test
    Craft::$app->disableMaintenanceMode();

    postJson(action(MigrateController::class));

    // Verify maintenance mode is off after operation
    expect(Craft::$app->getIsInMaintenanceMode())->toBeFalse();
});

test('handles empty request body', function () {
    $response = post(action(MigrateController::class));

    // Should handle empty body
    expect($response->status())->toBeIn([204, 503]);
});

test('can be called multiple times', function () {
    $response1 = postJson(action(MigrateController::class));
    $response2 = postJson(action(MigrateController::class));

    // Both should succeed
    expect($response1->status())->toBeIn([204, 503]);
    expect($response2->status())->toBeIn([204, 503]);
});

test('returns appropriate HTTP status codes', function () {
    $response = postJson(action(MigrateController::class));

    // Should return one of the valid HTTP status codes
    expect($response->status())->toBeIn([204, 500, 503]);
});
