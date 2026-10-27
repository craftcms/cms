<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\AuthenticateCraftSession;
use CraftCms\Cms\Http\Middleware\UseCraftAuthGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventStaleDatabaseReads;
use Illuminate\Routing\Router;

it('only prevents stale database reads on Craft routes', function () {
    $router = app(Router::class);

    expect(app(Kernel::class)->getGlobalMiddleware())->not->toContain(PreventStaleDatabaseReads::class)
        ->and($router->getMiddlewareGroups()['craft'][0])->toBe(UseCraftAuthGuard::class)
        ->and($router->getMiddlewareGroups()['craft'][1])->toBe(PreventStaleDatabaseReads::class)
        ->and($router->getMiddlewareGroups()['craft.web'])->toContain(AuthenticateCraftSession::class);
});
