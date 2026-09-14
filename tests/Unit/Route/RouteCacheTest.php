<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\Commands\RouteCacheCommand;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use CraftCms\Cms\Route\RouteServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\RouteCacheCommand as LaravelRouteCacheCommand;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as LaravelRouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->originalBootstrapPath = app()->bootstrapPath();
    $this->tempDir = sys_get_temp_dir().'/craft-route-cache-test-'.uniqid();
    File::ensureDirectoryExists($this->tempDir.'/cache');
    app()->useBootstrapPath($this->tempDir);

    $this->routes = new RouteCollection;
    $this->routes->add(new Route('POST', '{cpTrigger}/{actionTrigger}/login/{provider?}', fn () => 'login')
        ->name('cached-login')
        ->metadata(['craft.allowDuringMaintenance' => true])
        ->middleware('craft'));
    $this->routes->add(new Route('GET', 'restricted', fn () => 'restricted')->name('restricted'));
    app(Router::class)->setRoutes($this->routes);
});

afterEach(function () {
    app()->useBootstrapPath($this->originalBootstrapPath);
    File::deleteDirectory($this->tempDir);
    PreventRequestsDuringMaintenance::flushState();
});

it('registers the Craft route cache command', function () {
    expect(Artisan::all()['route:cache'])->toBeInstanceOf(RouteCacheCommand::class);
});

it('loads maintenance exceptions from route cache files with current triggers', function (string $commandClass) {
    app(Kernel::class)->registerCommand(new $commandClass(app(Filesystem::class)));

    $this->artisan('route:cache', ['--no-interaction' => true])->assertSuccessful();

    Cms::config()->cpTrigger('back-office');
    Cms::config()->actionTrigger('execute');
    PreventRequestsDuringMaintenance::flushState();
    PreventRequestsDuringMaintenance::except('host-health');

    new RouteServiceProvider(app())->register();
    new class(app()) extends LaravelRouteServiceProvider
    {
        public function load(): void
        {
            $this->loadCachedRoutes();
        }
    }->load();

    $routes = app(Router::class)->getRoutes();

    expect($routes)->toBeInstanceOf(CompiledRouteCollection::class)
        ->and($routes->match(Request::create('/back-office/execute/login/google', 'POST'))->getName())->toBe('cached-login')
        ->and(app(PreventRequestsDuringMaintenance::class)->getExcludedPaths())
        ->toBe(['host-health', 'back-office/execute/login/*']);

    $this->artisan('route:clear', ['--no-interaction' => true])->assertSuccessful();

    expect(file_exists(app()->getCachedRoutesPath()))->toBeFalse();
})->with([
    'Craft route cache' => [CraftMaintenanceRouteCacheCommand::class],
    'existing Laravel route cache' => [LegacyMaintenanceRouteCacheCommand::class],
]);

it('stores URI templates in the route cache and replaces them when rebuilt', function () {
    app(Kernel::class)->registerCommand(new CraftMaintenanceRouteCacheCommand(app(Filesystem::class)));

    $this->artisan('route:cache', ['--no-interaction' => true])->assertSuccessful();

    $templates = require app()->getCachedRoutesPath();

    expect($templates)->toBe(['{cpTrigger}/{actionTrigger}/login/{provider?}']);

    $routes = new RouteCollection;
    $routes->add(new Route('GET', 'replacement', fn () => 'replacement')->name('replacement'));
    app(Router::class)->setRoutes($routes);
    $this->artisan('route:cache', ['--no-interaction' => true])->assertSuccessful();

    $templates = require app()->getCachedRoutesPath();

    expect($templates)->toBe([])
        ->and(app(Router::class)->getRoutes()->getByName('cached-login'))->toBeNull()
        ->and(app(Router::class)->getRoutes()->getByName('replacement'))->not->toBeNull();

    PreventRequestsDuringMaintenance::flushState();
    $this->mock(Router::class)->shouldNotReceive('getRoutes');
    PreventRequestsDuringMaintenance::registerRouteExceptions($templates);

    expect(app(PreventRequestsDuringMaintenance::class)->getExcludedPaths())->toBe([]);
});

class CraftMaintenanceRouteCacheCommand extends RouteCacheCommand
{
    protected function getFreshApplicationRoutes(): RouteCollection
    {
        return app(Router::class)->getRoutes();
    }
}

class LegacyMaintenanceRouteCacheCommand extends LaravelRouteCacheCommand
{
    protected function getFreshApplicationRoutes(): RouteCollection
    {
        return app(Router::class)->getRoutes();
    }
}
