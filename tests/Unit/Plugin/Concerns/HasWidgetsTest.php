<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Dashboard\Widgets\Updates;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(RegisterWidgetTypes::class);
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured widget types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setWidgets([Updates::class]);
    $plugin->bootHasWidgets();

    $event = new RegisterWidgetTypes(new Collection);
    event($event);

    expect($event->types->all())->toContain(Updates::class);
});

it('does not register widget listeners when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasWidgets();

    $event = new RegisterWidgetTypes(new Collection);
    event($event);

    expect($event->types->all())->toBe([]);
});
