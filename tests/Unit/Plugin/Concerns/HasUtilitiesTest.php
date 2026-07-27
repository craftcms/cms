<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
    Volumes::shouldReceive('getAllVolumes')->andReturn(Collection::make());
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
