<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);

    $this->basePath = dirname(__DIR__, 3).'/TestClasses/TestPlugin/src';
});

afterEach(function () {
    Route::getRoutes()->refreshNameLookups();
    app()->forgetInstance(TestPlugin::class);
});

it('registers web, cp, and action routes from plugin route files', function () {
    /** @var GeneralConfig $config */
    $config = app(GeneralConfig::class);
    $config->cpTrigger = 'admin';
    $config->actionTrigger = 'actions';

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath($this->basePath);
    $plugin->bootHasRoutes();

    $uris = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)->toContain('plugin-web')
        ->and($uris)->toContain('{cpTrigger}/plugin-cp')
        ->and($uris)->toContain('{cpTrigger}/{actionTrigger}/test-plugin/plugin-action')
        ->and($uris)->toContain('{actionTrigger}/test-plugin/plugin-action')
        ->and($uris)->not()->toContain('{cpTrigger}/{actionTrigger}/plugin-action')
        ->and($uris)->not()->toContain('{actionTrigger}/plugin-action');

    $routesByUri = collect(Route::getRoutes()->getRoutes())
        ->keyBy(fn ($route) => $route->uri());

    expect($routesByUri->get('{cpTrigger}/plugin-cp')->middleware())->toContain('web', 'craft', 'craft.cp')
        ->and($routesByUri->get('{cpTrigger}/{actionTrigger}/test-plugin/plugin-action')->middleware())->toContain('web', 'craft', 'craft.cp');
});

it('registers root control panel routes when the cp trigger is null', function () {
    /** @var GeneralConfig $config */
    $config = app(GeneralConfig::class);
    $config->cpTrigger = null;
    $config->actionTrigger = 'actions';

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath($this->basePath);
    $plugin->bootHasRoutes();

    $routes = collect(Route::getRoutes()->getRoutes());
    $uris = $routes
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)->toContain('plugin-web')
        ->and($uris)->toContain('plugin-cp')
        ->and($uris)->toContain('{actionTrigger}/test-plugin/plugin-action')
        ->and($uris)->not()->toContain('{cpTrigger}/plugin-cp')
        ->and($uris)->not()->toContain('{cpTrigger}/{actionTrigger}/test-plugin/plugin-action');

    $pluginActionRoutes = $routes->filter(fn ($route) => $route->uri() === '{actionTrigger}/test-plugin/plugin-action');

    expect($routes->first(fn ($route) => $route->uri() === 'plugin-cp')->middleware())->toContain('web', 'craft', 'craft.cp');
    expect($pluginActionRoutes)->toHaveCount(1)
        ->and($pluginActionRoutes->first()->middleware())->toContain('web', 'craft', 'craft.cp');
});
