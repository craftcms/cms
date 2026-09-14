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

test('allows no-op deployments but rejects pending deployments during maintenance', function () {
    Auth::logout();
    app()->maintenanceMode()->activate([]);

    postJson(action(MigrateController::class))->assertNoContent();

    $this->mock(Updates::class)
        ->shouldReceive('isCraftUpdatePending')->andReturn(true)
        ->shouldReceive('isCraftSchemaVersionCompatible')->andReturn(true)
        ->shouldReceive('pendingMigrationHandles')->andReturn(['foo']);

    postJson(action(MigrateController::class))->assertServiceUnavailable();
});
