<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured field types', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setFieldTypes([TestPluginFieldType::class]);
    $plugin->bootHasFieldTypes();

    expect(app(Fields::class)->getAllFieldTypes())->toContain(TestPluginFieldType::class);
});

abstract class TestPluginFieldType extends Field {}
