<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\View\Events\CpTemplateRootsResolving;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;
use Illuminate\View\Factory as ViewFactory;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(CpTemplateRootsResolving::class);
    app()->forgetInstance(TestPlugin::class);
    Once::flush();
});

it('registers the plugin template roots when views or templates exist', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->bootHasViews();

    $event = new CpTemplateRootsResolving;
    event($event);

    expect($event->roots)
        ->toHaveKey('test-plugin')
        ->and($event->roots['test-plugin'])
        ->toBe([
            dirname(__DIR__, 3).'/TestClasses/TestPlugin/resources/views',
            dirname(__DIR__, 3).'/TestClasses/TestPlugin/resources/templates',
        ]);
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

    $event = new CpTemplateRootsResolving;
    event($event);

    expect($event->roots)->not->toHaveKey('other-plugin');

    File::deleteDirectory(dirname($emptyPluginPath, 2));
});

it('renders plugin templates with Laravel namespaced view syntax', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->bootHasViews();

    Once::flush();
    TemplateMode::set(TemplateMode::Cp);

    /** @var ViewFactory $viewFactory */
    $viewFactory = app(ViewFactory::class);
    $viewFactory->getFinder()->flush();

    foreach (TemplateMode::get()->templateRoots() as $namespace => $roots) {
        $viewFactory->addNamespace($namespace, $roots);
    }

    expect(view('test-plugin::tokens.index', ['value' => 'namespaced'])->render())
        ->toContain('Plugin tokens index: namespaced');
});
