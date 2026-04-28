<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\RegisterElementTypes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers configured element types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setElementTypes([TestPluginElementType::class]);
    $plugin->bootHasElementTypes();

    event($event = new RegisterElementTypes([]));

    expect($event->types)->toContain(TestPluginElementType::class);
});

it('does not register element type listeners when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasElementTypes();

    event($event = new RegisterElementTypes([]));

    expect($event->types)->toBe([]);
});

abstract class TestPluginElementType extends Element {}
