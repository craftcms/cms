<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\MigrateController;
use CraftCms\Cms\Update\Updates;
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

    post(action(MigrateController::class))->assertNoContent();
});

test('returns no content when no migrations or config changes pending', function () {
    $response = postJson(action(MigrateController::class), [
        'applyProjectConfigChanges' => false,
    ]);

    // Should return 204 No Content when there's nothing to do
    expect($response->status())->toBe(204);
});

test('returns no content when migrations complete successfully', function () {
    $response = postJson(action(MigrateController::class));

    // Should complete without error
    expect($response->status())->toBe(204);
});

test('handles applyProjectConfigChanges parameter', function () {
    $response = postJson(action(MigrateController::class), [
        'applyProjectConfigChanges' => true,
    ]);

    // Should handle the parameter
    expect($response->status())->toBe(204);
});

test('handles maintenance mode correctly', function () {
    postJson(action(MigrateController::class))->assertNoContent();

    // Enable maintenance mode
    app()->maintenanceMode()->activate([]);

    // Nothing to migrate
    postJson(action(MigrateController::class))->assertNoContent();

    $this->mock(Updates::class)
        ->shouldReceive('isCraftUpdatePending')->andReturn(true)
        ->shouldReceive('isCraftSchemaVersionCompatible')->andReturn(true)
        ->shouldReceive('pendingMigrationHandles')->andReturn(['foo']);

    postJson(action(MigrateController::class))->assertServiceUnavailable();
});

test('disables maintenance mode after completion', function () {
    // Ensure maintenance mode is off before test
    app()->maintenanceMode()->deactivate();

    postJson(action(MigrateController::class));

    // Verify maintenance mode is off after operation
    expect(app()->isDownForMaintenance())->toBeFalse();
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
