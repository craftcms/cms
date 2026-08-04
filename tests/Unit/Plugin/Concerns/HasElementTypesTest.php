<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers configured element types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setElementTypes([TestPluginElementType::class]);
    $plugin->bootHasElementTypes();

    expect(app(Elements::class)->getAllElementTypes())->toContain(TestPluginElementType::class);
});

abstract class TestPluginElementType extends Element {}
