<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\PermissionGroupCatalog;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

afterEach(fn () => app()->forgetInstance(TestPlugin::class));

it('registers a permission group for the plugin permissions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setPermissions([
        new Permission('managePlugin', 'Manage plugin'),
    ]);

    $plugin->bootHasPermissions();

    $groups = app(PermissionGroupCatalog::class)->apply(collect());

    expect($groups)->toHaveCount(1)
        ->and($groups->first()->handle)->toBe('plugin:test-plugin')
        ->and($groups->first()->heading)->toBe('Test Plugin')
        ->and($groups->first()->permissions->pluck('key')->all())->toBe(['managePlugin']);
});

it('resolves the current plugin permissions on each catalog rebuild', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasPermissions();
    $registry = app(PermissionGroupCatalog::class);

    expect($registry->apply(collect()))->toBeEmpty();

    $plugin->setPermissions([
        new Permission('managePlugin', 'Manage plugin'),
    ]);

    expect($registry->apply(collect())->first()->permissions->pluck('key')->all())
        ->toBe(['managePlugin']);
});

it('rejects invalid permission definitions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setPermissions(['not-a-permission']);

    $plugin->bootHasPermissions();

    app(PermissionGroupCatalog::class)->apply(collect());
})->throws(Exception::class, sprintf(
    'Each permission returned from `getPermissions()` needs to be an instance of `%s`',
    Permission::class,
));
