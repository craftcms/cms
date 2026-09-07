<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Support\Facades\Route;

it('reuses compiled routes without sharing route mutations between applications', function () {
    $this->refreshApplication();

    expect(Route::getRoutes())->toBeInstanceOf(CompiledRouteCollection::class);

    $dashboard = Route::getRoutes()->getByName('craft.cp.dashboard');
    $middleware = $dashboard->middleware();
    $dashboard->middleware('unit-test-only');
    Route::get('unit-test-only', fn () => 'test')->name('unit-test-only');

    $this->refreshApplication();

    $routes = Route::getRoutes();

    expect($routes)->toBeInstanceOf(CompiledRouteCollection::class)
        ->and($routes->getByName('unit-test-only'))->toBeNull()
        ->and($routes->getByName('craft.cp.dashboard'))->not->toBe($dashboard)
        ->and($routes->getByName('craft.cp.dashboard')->middleware())->toBe($middleware)
        ->and($routes->match(Request::create(route('craft.cp.dashboard')))->getName())->toBe('craft.cp.dashboard');
});
