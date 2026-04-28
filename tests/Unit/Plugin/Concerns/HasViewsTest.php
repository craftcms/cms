<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\View\Events\RegisterCpTemplateRoots;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(RegisterCpTemplateRoots::class);
    app()->forgetInstance(TestPlugin::class);
});

it('registers the plugin template root when templates exist', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->bootHasViews();

    $event = new RegisterCpTemplateRoots;
    event($event);

    expect($event->roots)->toHaveKey('test-plugin', dirname(__DIR__, 3).'/TestClasses/TestPlugin/resources/views');
});

it('does not register a template root when no templates directory exists', function () {
    $emptyPluginPath = storage_path('framework/testing/plugins/empty-plugin/src');
    File::ensureDirectoryExists($emptyPluginPath);

    $plugin = TestPlugin::create([
        'handle' => 'other-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath($emptyPluginPath);
    $plugin->bootHasViews();

    $event = new RegisterCpTemplateRoots;
    event($event);

    expect($event->roots)->not->toHaveKey('other-plugin');

    File::deleteDirectory(dirname($emptyPluginPath, 2));
});
