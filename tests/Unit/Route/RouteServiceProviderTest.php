<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

it('only applies write connection routing to Craft routes', function () {
    $router = app(Router::class);

    expect(app(Kernel::class)->getGlobalMiddleware())->not->toContain(UseWriteConnection::class)
        ->and($router->getMiddlewareGroups()['craft'][0])->toBe(UseWriteConnection::class);
});
