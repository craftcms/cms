<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

const PLUGIN_BASE_PATH = __DIR__.'/../../../TestClasses/TestPlugin/src';

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);

    ServiceProvider::$publishes = [];
    ServiceProvider::$publishGroups = [];
});

it('merges plugin config and registers publish paths', function () {
    Config::set('craft.test-plugin', [
        'shared' => 'app',
    ]);

    /** @var TestPlugin $plugin */
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(PLUGIN_BASE_PATH);
    $plugin->bootHasConfig();

    expect(Config::get('craft.test-plugin'))->toBe([
        'fromFile' => 'default',
        'shared' => 'app',
    ]);

    expect(ServiceProvider::pathsToPublish(TestPlugin::class, 'test-plugin'))->toBe([
        PLUGIN_BASE_PATH.'/config/test-plugin.php' => config_path('craft/test-plugin.php'),
    ]);
});

it('does not register config when disabled', function () {
    Config::set('craft.test-plugin', [
        'shared' => 'app',
    ]);

    /** @var TestPlugin $plugin */
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(PLUGIN_BASE_PATH);
    $plugin->config = false;
    $plugin->bootHasConfig();

    expect(Config::get('craft.test-plugin'))->toBe([
        'shared' => 'app',
    ]);

    expect(ServiceProvider::pathsToPublish(TestPlugin::class))->toBe([]);
});

it('does not register config when the plugin config file is missing', function () {
    /** @var TestPlugin $plugin */
    $plugin = TestPlugin::create([
        'handle' => 'missing-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasConfig();

    expect(Config::get('craft.missing-plugin'))->toBeNull();
    expect(ServiceProvider::pathsToPublish(TestPlugin::class))->toBe([]);
});
