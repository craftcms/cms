<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;

it('exposes the complete local filesystem settings as a typed form', function () {
    $filesystem = new Local([
        'hasUrls' => true,
        'url' => 'https://example.test/uploads',
        'path' => '/tmp/uploads',
    ]);

    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm(), new FormContext(
        namespace: 'settings',
    ));
    $controls = collect(flattenFormNodes(array_map(
        fn ($node): array => $node->jsonSerialize(),
        $payload->nodes,
    )))->pluck('control')->filter()->values();

    expect($controls->map(
        fn (array $control): string => implode('.', array_slice($control['path'], 1)),
    )->all())->toBe(['hasUrls', 'url', 'path'])
        ->and($controls->pluck('type')->all())->toBe([
            Lightswitch::class,
            Text::class,
            Text::class,
        ])
        ->and($controls[2]['props']['textExpanderTriggers'])->not->toBeEmpty()
        ->and($payload->values)->toBe([
            'settings' => [
                'hasUrls' => true,
                'url' => 'https://example.test/uploads',
                'path' => '/tmp/uploads',
            ],
        ])
        ->and($filesystem->getSettings())->toBe([
            'hasUrls' => true,
            'url' => 'https://example.test/uploads',
            'path' => '/tmp/uploads',
        ]);
});

it('omits inactive URL settings and resolves read only mode', function () {
    $filesystem = new Local(['hasUrls' => false, 'path' => '@webroot/uploads']);
    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm(), new FormContext(
        namespace: 'settings',
        mode: ControlMode::ReadOnly,
    ));
    $controls = collect(flattenFormNodes(array_map(
        fn ($node): array => $node->jsonSerialize(),
        $payload->nodes,
    )))->pluck('control')->filter()->values();

    expect($controls->map(
        fn (array $control): string => implode('.', array_slice($control['path'], 1)),
    )->all())->toBe(['hasUrls', 'path'])
        ->and($controls->pluck('mode')->all())->each->toBe(ControlMode::ReadOnly->value);
});

it('does not expose settings for the temporary filesystem', function () {
    expect(new Temp()->settingsForm())->toBeNull();
});
