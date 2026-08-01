<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\Temp;

it('projects the complete local filesystem settings surface', function () {
    $filesystem = new Local([
        'hasUrls' => true,
        'url' => '@web/uploads',
        'path' => '@webroot/uploads',
    ]);

    $definition = $filesystem->getSettingsFormDefinition(false)?->toArray();
    $fields = collect($definition['elements'] ?? [])->keyBy(
        fn (array $field): string => $field['children'][0]['name'],
    );

    expect($fields->keys()->all())->toBe(['hasUrls', 'url', 'path'])
        ->and($fields['hasUrls'])->toMatchArray([
            'props' => ['label' => 'Files in this filesystem have public URLs'],
            'children' => [[
                'type' => 'craft:lightswitch-input',
                'name' => 'hasUrls',
            ]],
        ])
        ->and($fields['url'])->toMatchArray([
            'props' => [
                'label' => 'Base URL',
                'instructions' => 'The base URL to the files in this filesystem.',
                'required' => true,
            ],
            'visibleWhen' => [
                'name' => 'hasUrls',
                'operator' => 'equals',
                'value' => true,
            ],
        ])
        ->and($fields['url']['children'][0])->toMatchArray([
            'type' => 'craft:combobox-input',
            'name' => 'url',
        ])
        ->and($fields['url']['children'][0]['props'])->toMatchArray([
            'placeholder' => '//example.com/path/to/folder',
        ])
        ->and($fields['url']['children'][0]['props']['options'])->toBeArray()
        ->and($fields['path'])->toMatchArray([
            'props' => [
                'label' => 'Base Path',
                'instructions' => 'The base folder path that should be used as the root of the filesystem.',
                'required' => true,
            ],
        ])
        ->and($fields['path']['children'][0])->toMatchArray([
            'type' => 'craft:combobox-input',
            'name' => 'path',
        ])
        ->and($fields['path']['children'][0]['props'])->toMatchArray([
            'placeholder' => '/path/to/folder',
            'allowAliases' => true,
        ])
        ->and($fields['path']['children'][0]['props']['options'])->toBeArray();
});

it('projects local filesystem settings as read only', function () {
    $definition = new Local()->getSettingsFormDefinition(true)?->toArray();

    expect($definition['elements'] ?? [])->toHaveCount(3);

    foreach ($definition['elements'] ?? [] as $field) {
        expect($field['props']['readOnly'] ?? false)->toBeTrue();
    }
});

it('returns no settings definition for the temporary filesystem', function () {
    expect(new Temp()->getSettingsFormDefinition(false))->toBeNull();
});
