<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

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

    $plugin->bootHasConfig();

    expect(Config::get('craft.test-plugin'))->toBe([
        'fromFile' => 'default',
        'shared' => 'app',
    ]);

    expect(ServiceProvider::pathsToPublish(TestPlugin::class, 'test-plugin'))->toBe([
        dirname(__DIR__, 3).'/TestClasses/config/test-plugin.php' => config_path('craft/test-plugin.php'),
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
