<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    YiiEvent::off(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES);
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    YiiEvent::off(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES);
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured element types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setElementTypes([TestPluginElementType::class]);
    $plugin->bootHasElementTypes();

    $elements = new Elements;
    $event = new RegisterComponentTypesEvent;
    $elements->trigger(Elements::EVENT_REGISTER_ELEMENT_TYPES, $event);

    expect($event->types)->toContain(TestPluginElementType::class);
});

it('does not register element type listeners when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasElementTypes();

    $elements = new Elements;
    $event = new RegisterComponentTypesEvent;
    $elements->trigger(Elements::EVENT_REGISTER_ELEMENT_TYPES, $event);

    expect($event->types)->toBe([]);
});

abstract class TestPluginElementType extends Element {}
