<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Facades\Fields;

/**
 * Maps every setting in a resolved Assets Form to whether it renders.
 *
 * @param  list<NodePayload>  $nodes
 * @return array<string, bool>
 */
function visibilityByName(array $nodes, bool $inheritedHidden = false): array
{
    $visibility = [];

    foreach ($nodes as $node) {
        $hidden = $inheritedHidden || ($node->props['hidden'] ?? false);

        if ($node->control !== null) {
            $visibility[end($node->control->path)] = ! $hidden;

            continue;
        }

        if ($node->children !== null && $node->children !== []) {
            $visibility = [...$visibility, ...visibilityByName($node->children, $hidden)];
        }
    }

    return $visibility;
}

/** @return array<string, bool> */
function assetsSettingsVisibility(array $settings = []): array
{
    $field = Fields::createField(['type' => Assets::class, 'settings' => $settings]);
    $context = new FormContext(namespace: 'settings');

    return visibilityByName(
        app(FormResolver::class)->resolve($field->settingsForm($context), $context)->nodes,
    );
}

it('shows the unrestricted location settings by default', function () {
    $visible = assetsSettingsVisibility();

    expect($visible)
        ->toMatchArray([
            'sources' => true,
            'defaultUploadLocationSource' => true,
            'defaultUploadLocationSubpath' => true,
            'restrictedLocationSource' => false,
            'restrictedLocationSubpath' => false,
            'allowSubfolders' => false,
            'restrictedDefaultUploadSubpath' => false,
        ]);
});

it('swaps to the restricted location settings when restrictLocation is on', function () {
    $visible = assetsSettingsVisibility(['restrictLocation' => true]);

    expect($visible)
        ->toMatchArray([
            'restrictedLocationSource' => true,
            'restrictedLocationSubpath' => true,
            'allowSubfolders' => true,
            'sources' => false,
            'defaultUploadLocationSource' => false,
            'defaultUploadLocationSubpath' => false,
        ]);
});

it('only shows the restricted upload subpath once subfolders are allowed', function () {
    expect(assetsSettingsVisibility(['restrictLocation' => true])['restrictedDefaultUploadSubpath'])
        ->toBeFalse()
        ->and(assetsSettingsVisibility([
            'restrictLocation' => true,
            'allowSubfolders' => true,
        ])['restrictedDefaultUploadSubpath'])->toBeTrue()
        // Subfolders alone aren't enough — the whole restricted block is hidden.
        ->and(assetsSettingsVisibility(['allowSubfolders' => true])['restrictedDefaultUploadSubpath'])
        ->toBeFalse();
});

it('only shows the allowed file kinds when file types are restricted', function () {
    expect(assetsSettingsVisibility()['allowedKinds'])->toBeFalse()
        ->and(assetsSettingsVisibility(['restrictFiles' => true])['allowedKinds'])->toBeTrue();
});

it('only shows the search input setting when the field draws from one place', function () {
    expect(assetsSettingsVisibility()['showSearchInput'])->toBeFalse()
        ->and(assetsSettingsVisibility(['sources' => '*'])['showSearchInput'])->toBeFalse()
        ->and(assetsSettingsVisibility(['sources' => ['volume:a', 'volume:b']])['showSearchInput'])->toBeFalse()
        ->and(assetsSettingsVisibility(['sources' => ['volume:a']])['showSearchInput'])->toBeTrue()
        ->and(assetsSettingsVisibility(['restrictLocation' => true])['showSearchInput'])->toBeTrue();
});

it('keeps hidden settings in the payload so their values still post', function () {
    $field = Fields::createField(['type' => Assets::class, 'settings' => [
        'restrictLocation' => false,
        'restrictedLocationSubpath' => 'kept/while/hidden',
    ]]);
    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);

    expect(assetsSettingsVisibility()['restrictedLocationSubpath'])->toBeFalse()
        ->and($payload->values['settings']['restrictedLocationSubpath'])->toBe('kept/while/hidden');
});
