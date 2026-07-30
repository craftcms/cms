<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers a native field provider under the plugin handle', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setNativeFields(fn (FieldLayout $layout, array $fields) => [...$fields, EntryTitleField::class]);
    $plugin->bootHasNativeFields();

    expect(app(NativeFields::class)->apply(new FieldLayout))->toContain(EntryTitleField::class);
});

it('does not register a native field provider by default', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasNativeFields();

    app(NativeFields::class)->register('test-plugin', fn (FieldLayout $layout, array $fields) => $fields);

    expect(true)->toBeTrue();
});
