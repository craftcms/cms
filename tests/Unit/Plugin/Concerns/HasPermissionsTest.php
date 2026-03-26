<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Events\RegisterUserPermissions;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app(Dispatcher::class)->forget(RegisterUserPermissions::class);
    app()->forgetInstance(TestPlugin::class);
});

it('registers a permission group for the plugin permissions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setPermissions([
        new Permission('managePlugin', 'Manage plugin'),
    ]);

    $plugin->bootHasPermissions();

    $event = new RegisterUserPermissions(new Collection);
    event($event);

    expect($event->permissions)->toHaveCount(1)
        ->and($event->permissions->first()->heading)->toBe('Test Plugin')
        ->and($event->permissions->first()->permissions->pluck('key')->all())->toBe(['managePlugin']);
});

it('rejects invalid permission definitions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setPermissions(['not-a-permission']);

    $plugin->bootHasPermissions();
})->throws(Exception::class, sprintf(
    'Each permission returned from `getPermissions()` needs to be an instance of `%s`',
    Permission::class,
));
