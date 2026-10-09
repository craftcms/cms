<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Testing\CachedState;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->setUpWithCachedRoutes();
    $this->restoreCachedMaintenanceRouteExceptions();
});

afterEach(function () {
    CachedState::$cachedRoutes = null;
});

it('restores maintenance exceptions without enumerating routes and preserves host exceptions', function () {
    $middleware = app(PreventRequestsDuringMaintenance::class);
    $paths = $middleware->getExcludedPaths();

    PreventRequestsDuringMaintenance::flushState();
    PreventRequestsDuringMaintenance::except('host-health');
    $this->mock(Router::class)->shouldNotReceive('getRoutes');

    $this->restoreCachedMaintenanceRouteExceptions();

    expect($paths)->not->toBeEmpty()
        ->and($middleware->getExcludedPaths())->toBe(['host-health', ...$paths]);
});

it('rebuilds maintenance exception paths when a trigger changes', function (string $setting, string $suffix) {
    $original = Cms::config()->$setting;
    $middleware = app(PreventRequestsDuringMaintenance::class);
    $paths = $middleware->getExcludedPaths();

    Cms::config()->$setting('custom-trigger');
    PreventRequestsDuringMaintenance::flushState();
    $this->restoreCachedMaintenanceRouteExceptions();

    expect($middleware->getExcludedPaths())
        ->toContain("custom-trigger/$suffix")
        ->not->toContain(trim((string) $original, '/')."/$suffix");

    Cms::config()->$setting($original);
    PreventRequestsDuringMaintenance::flushState();
    $this->restoreCachedMaintenanceRouteExceptions();

    expect($middleware->getExcludedPaths())->toBe($paths);
})->with([
    'control panel trigger' => ['cpTrigger', 'settings/general'],
    'action trigger' => ['actionTrigger', 'users/login'],
]);

it('restores Craft exception tracking so cached paths do not bypass host route maintenance', function () {
    $routes = Route::getRoutes();
    Route::setRoutes(new RouteCollection);
    PreventRequestsDuringMaintenance::registerRouteExceptions();
    Route::setRoutes($routes);

    PreventRequestsDuringMaintenance::flushState();
    $this->restoreCachedMaintenanceRouteExceptions();

    $path = Cms::config()->actionTrigger.'/users/login';
    Route::get($path, fn () => 'host route');
    auth()->logout();
    app()->maintenanceMode()->activate([]);

    $this->get($path)->assertServiceUnavailable();
});

it('rebuilds maintenance exceptions when the route cache is invalidated', function () {
    Route::allowDuringMaintenance()->get('new-maintenance-exception', fn () => 'ok');
    CachedState::$cachedRoutes = null;
    $this->setUpWithCachedRoutes();

    PreventRequestsDuringMaintenance::flushState();
    $this->restoreCachedMaintenanceRouteExceptions();

    expect(app(PreventRequestsDuringMaintenance::class)->getExcludedPaths())
        ->toContain('new-maintenance-exception');
});
