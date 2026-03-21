<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('compares plugin editions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
        'edition' => 'pro',
    ]);

    expect($plugin->is('standard'))->toBeFalse()
        ->and($plugin->is('standard', '>'))->toBeTrue()
        ->and($plugin->is('pro'))->toBeTrue();
});

it('rejects unsupported editions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->is('enterprise');
})->throws(InvalidArgumentException::class, 'Unsupported edition: enterprise');

it('rejects invalid comparison operators', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->is('standard', 'roughly');
})->throws(InvalidArgumentException::class, 'Invalid edition comparison operator: roughly');
