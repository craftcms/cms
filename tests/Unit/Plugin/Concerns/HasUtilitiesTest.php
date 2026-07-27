<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
    Volumes::shouldReceive('getAllVolumes')->andReturn(Collection::make());
});

afterEach(function () {
    ClearCaches::flushState();
});

it('registers configured utility types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setUtilities([TestPluginUtilityType::class]);
    $plugin->bootHasUtilities();

    expect(app(Utilities::class)->getAllUtilityTypes())->toContain(TestPluginUtilityType::class);
});

it('registers configured cache options and tags', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setCacheOptions([
        'test-plugin' => [
            'label' => 'Test Plugin caches',
            'action' => fn () => null,
        ],
    ]);
    $plugin->setCacheTags(['test-plugin' => 'Test Plugin caches']);
    $plugin->bootHasUtilities();

    expect(ClearCaches::cacheOptions())->toContain([
        'label' => 'Test Plugin caches',
        'action' => $plugin->customCacheOptions['test-plugin']['action'],
        'key' => 'test-plugin',
    ])->and(ClearCaches::tagOptions())->toContain([
        'tag' => 'test-plugin',
        'label' => 'Test Plugin caches',
    ]);
});

class TestPluginUtilityType extends Utility
{
    public static function displayName(): string
    {
        return 'Test plugin utility';
    }

    public static function id(): string
    {
        return 'test-plugin-utility';
    }

    public static function contentHtml(): string
    {
        return '';
    }
}
