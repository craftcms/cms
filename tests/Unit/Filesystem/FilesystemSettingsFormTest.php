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
        'url' => '@web/uploads',
        'path' => '@webroot/uploads',
    ]);

    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm(), new FormContext(
        namespace: 'settings',
    ));

    expect(array_map(
        fn ($node): string => implode('.', array_slice($node->control->path, 1)),
        $payload->nodes,
    ))->toBe(['hasUrls', 'url', 'path'])
        ->and(array_map(fn ($node): string => $node->control->type, $payload->nodes))->toBe([
            Lightswitch::class,
            Text::class,
            Text::class,
        ])
        ->and($payload->nodes[2]->control->props['textExpanderTriggers'])->not->toBeEmpty()
        ->and($payload->values)->toBe([
            'settings' => [
                'hasUrls' => true,
                'url' => '@web/uploads',
                'path' => '@webroot/uploads',
            ],
        ]);
});

it('omits inactive URL settings and resolves read only mode', function () {
    $filesystem = new Local(['hasUrls' => false, 'path' => '@webroot/uploads']);
    $payload = app(FormResolver::class)->resolve($filesystem->settingsForm(), new FormContext(
        namespace: 'settings',
        mode: ControlMode::ReadOnly,
    ));

    expect(array_map(
        fn ($node): string => implode('.', array_slice($node->control->path, 1)),
        $payload->nodes,
    ))->toBe(['hasUrls', 'path'])
        ->and(array_map(
            fn ($node): ControlMode => $node->control->mode,
            $payload->nodes,
        ))->each->toBe(ControlMode::ReadOnly);
});

it('does not expose settings for the temporary filesystem', function () {
    expect(new Temp()->settingsForm())->toBeNull();
});
