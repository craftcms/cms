<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(RegisterFieldTypes::class);
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured field types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setFieldTypes([PlainText::class]);
    $plugin->bootHasFieldTypes();

    $event = new RegisterFieldTypes(new Collection);
    event($event);

    expect($event->types->all())->toContain(PlainText::class);
});

it('does not register field type listeners when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasFieldTypes();

    $event = new RegisterFieldTypes(new Collection);
    event($event);

    expect($event->types->all())->toBe([]);
});
