<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured widget types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setWidgets([TestPluginWidgetType::class]);
    $plugin->bootHasWidgets();

    expect(app(WidgetTypes::class)->types())->toContain(TestPluginWidgetType::class);
});

abstract class TestPluginWidgetType extends Widget {}
