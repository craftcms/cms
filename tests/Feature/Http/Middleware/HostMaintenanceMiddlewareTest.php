<?php

declare(strict_types=1);

use CraftCms\Cms\Route\RouteServiceProvider;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMaintenanceMiddleware;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

class HostMaintenanceMiddlewareForTest extends LaravelMaintenanceMiddleware
{
    public function handle($request, Closure $next): mixed
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return $next($request);
            }
        }

        if ($this->app->maintenanceMode()->active()) {
            return response('Host maintenance response', 521);
        }

        return $next($request);
    }
}

test('host maintenance middleware applies to host routes without blocking Craft access', function () {
    $kernel = app(Kernel::class);
    $originalMiddleware = $kernel->getGlobalMiddleware();
    $kernel->setGlobalMiddleware(array_map(
        fn (string $middleware): string => $middleware === LaravelMaintenanceMiddleware::class
            ? HostMaintenanceMiddlewareForTest::class
            : $middleware,
        $originalMiddleware,
    ));
    new RouteServiceProvider(app())->register();

    try {
        Route::middleware('web')->get('host-maintenance-test', fn () => 'ok');
        actingAs(User::findOne());
        app()->maintenanceMode()->activate([]);

        get('/host-maintenance-test')
            ->assertStatus(521)
            ->assertSeeText('Host maintenance response');

        get(cp_url('dashboard'))->assertOk();
    } finally {
        $kernel->setGlobalMiddleware($originalMiddleware);
    }
});
