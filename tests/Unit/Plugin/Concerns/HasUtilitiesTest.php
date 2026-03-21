<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Utility\Events\RegisterUtilities;
use CraftCms\Cms\Utility\Utilities\PhpInfo;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(RegisterUtilities::class);
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured utility types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setUtilities([PhpInfo::class]);
    $plugin->bootHasUtilities();

    $event = new RegisterUtilities(new Collection);
    event($event);

    expect($event->types->all())->toContain(PhpInfo::class);
});

it('does not register utility listeners when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasUtilities();

    $event = new RegisterUtilities(new Collection);
    event($event);

    expect($event->types->all())->toBe([]);
});
