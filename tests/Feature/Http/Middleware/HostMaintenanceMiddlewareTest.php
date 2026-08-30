<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance as CraftMaintenanceMiddleware;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMaintenanceMiddleware;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('Craft maintenance middleware replaces Laravel middleware without exposing host routes', function () {
    $kernel = app(Kernel::class);

    expect($kernel->getGlobalMiddleware())
        ->toContain(CraftMaintenanceMiddleware::class)
        ->not->toContain(LaravelMaintenanceMiddleware::class);

    Route::middleware('web')->get('host-maintenance-test', fn () => 'ok');
    actingAs(User::findOne());
    app()->maintenanceMode()->activate([]);

    get('/host-maintenance-test')->assertServiceUnavailable();

    get(cp_url('dashboard'))->assertOk();
});
