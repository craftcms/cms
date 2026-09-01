<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Settings;

use function CraftCms\Cms\t;

it('adds settings lazily for the current mode', function () {
    $settings = app(Settings::class);
    $resolved = false;
    $settings->registerSetting('Modules', 'normal', function (Settings $settings) use (&$resolved) {
        $resolved = $settings === app(Settings::class);

        return ['label' => 'Normal'];
    });
    $settings->registerReadOnlySetting('Modules', 'readonly', fn () => ['label' => 'Readonly']);

    expect($resolved)->toBeFalse()
        ->and($settings->apply([], false))->toBe(['Modules' => ['normal' => ['label' => 'Normal']]])
        ->and($resolved)->toBeTrue()
        ->and($settings->apply([], true))->toBe(['Modules' => ['readonly' => ['label' => 'Readonly']]]);
});

it('preserves section and item registration order', function () {
    $settings = app(Settings::class);
    $settings->registerSetting('Modules', 'first', fn () => ['label' => 'First']);
    $settings->registerSetting('Modules', 'second', fn () => ['label' => 'Second']);
    $settings->registerSetting('Plugins', 'plugin', fn () => ['label' => 'Plugin']);

    expect($settings->apply([], false))->toBe([
        'Modules' => [
            'first' => ['label' => 'First'],
            'second' => ['label' => 'Second'],
        ],
        'Plugins' => ['plugin' => ['label' => 'Plugin']],
    ]);
});

it('rejects duplicate and invalid settings', function () {
    $settings = app(Settings::class);
    $settings->registerSetting('Modules', 'plugin', fn () => ['label' => 'Plugin']);

    expect(fn () => $settings->registerSetting('Modules', 'plugin', fn () => ['label' => 'Plugin']))
        ->toThrow(InvalidArgumentException::class);

    $settings->registerSetting('Modules', 'invalid', fn () => ['label' => null]);

    expect(fn () => $settings->apply([], false))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects settings that overwrite an existing item', function () {
    $settings = app(Settings::class);
    $settings->registerSetting('System', 'general', fn () => ['label' => 'General']);

    expect(fn () => $settings->apply(['System' => ['general' => ['label' => 'General']]], false))
        ->toThrow(InvalidArgumentException::class);
});

it('resolves section labels for the current locale', function () {
    $locale = app()->getLocale();
    $settings = app(Settings::class);
    $settings->registerSetting('System', 'plugin', fn () => ['label' => 'Plugin']);
    app()->setLocale('fr');

    try {
        expect($settings->apply([], false))->toHaveKey(t('System'));
    } finally {
        app()->setLocale($locale);
    }
});
