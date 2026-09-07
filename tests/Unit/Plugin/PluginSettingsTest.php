<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\SnapshotPluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettings;

it('creates independent concrete settings with explicit fluent setters', function () {
    $settings = TestPluginSettings::create();

    expect($settings->foo('File')->bar(null))->toBe($settings)
        ->and($settings->validationData())->toBe(['foo' => 'File', 'bar' => null])
        ->and(TestPluginSettings::create())->not->toBe($settings)
        ->and(TestPluginSettings::create()->foo)->toBeNull();
});

it('includes defaults and explicit nulls without exporting builder state', function () {
    $settings = SnapshotPluginSettings::create();

    expect($settings->getConstructorValue())->toBe('constructed')
        ->and($settings->validationData())->toBe([
            'enabled' => false, 'title' => 'Default', 'nested' => ['default' => true], 'callback' => null,
        ])
        ->and($settings->configData())->toBe($settings->validationData())
        ->and($settings->title(null))->toBe($settings)
        ->and($settings->validationData()['title'])->toBeNull()
        ->and($settings->configData())->toBe([
            'enabled' => false, 'title' => null, 'nested' => ['default' => true], 'callback' => null,
        ]);
});

it('delegates configuration data to customized validation data by default', function () {
    $settings = new class extends TestPluginSettings
    {
        public function validationData(): array
        {
            return ['custom' => 'snapshot'];
        }
    };

    expect($settings->configData())->toBe(['custom' => 'snapshot']);
});
