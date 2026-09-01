<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\FilesystemTypes;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers configured filesystem types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setFilesystemTypes([TestPluginFilesystemType::class]);
    $plugin->bootHasFilesystemTypes();

    expect(app(FilesystemTypes::class)->types())->toContain(TestPluginFilesystemType::class);
});

abstract class TestPluginFilesystemType extends Local {}
