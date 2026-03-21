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
        ->and($uris)->toContain('admin/plugin-cp')
        ->and($uris)->toContain('admin/actions/plugin-action')
        ->and($uris)->toContain('actions/plugin-action');
});
