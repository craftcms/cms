<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

use function CraftCms\Cms\t;

beforeEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

it('loads translations from the plugin translation directory', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->bootHasTranslations();

    expect($plugin->t9nCategory)->toBe('test-plugin')
        ->and(t('Hello', category: 'test-plugin', locale: 'en-US'))->toBe('Howdy');
});

it('respects an explicitly configured translation category', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
        't9nCategory' => 'custom-plugin',
    ]);

    $plugin->useBasePath(dirname(__DIR__, 3).'/TestClasses/TestPlugin/src');
    $plugin->bootHasTranslations();

    expect($plugin->t9nCategory)->toBe('custom-plugin');
});
