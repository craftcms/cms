<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('returns the default cp nav item', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    expect($plugin->getCpNavItem())->toBe([
        'label' => 'Test Plugin',
        'url' => 'test-plugin',
    ]);
});
