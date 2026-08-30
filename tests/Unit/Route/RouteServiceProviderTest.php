<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance as CraftMaintenanceMiddleware;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use CraftCms\Cms\Route\RouteServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMaintenanceMiddleware;
use Illuminate\Routing\Router;

class HostMaintenanceMiddleware extends LaravelMaintenanceMiddleware {}

it('only applies write connection routing to Craft routes', function () {
    $router = app(Router::class);

    expect(app(Kernel::class)->getGlobalMiddleware())->not->toContain(UseWriteConnection::class)
        ->and($router->getMiddlewareGroups()['craft'][0])->toBe(UseWriteConnection::class);
});

test('it preserves host maintenance middleware subclasses', function () {
    $kernel = app(Kernel::class);
    $originalMiddleware = $kernel->getGlobalMiddleware();
    $kernel->setGlobalMiddleware([HostMaintenanceMiddleware::class]);

    try {
        new RouteServiceProvider(app())->register();

        expect($kernel->getGlobalMiddleware())
            ->toContain(HostMaintenanceMiddleware::class)
            ->not->toContain(CraftMaintenanceMiddleware::class);
    } finally {
        $kernel->setGlobalMiddleware($originalMiddleware);
    }
});
