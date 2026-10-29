<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\FieldContext;

it('passes option icons and colors to its control', function (string $fieldType) {
    $field = new $fieldType([
        'options' => [
            ['label' => 'Icon', 'value' => 'icon', 'icon' => 'star', 'color' => 'ff0000', 'default' => false],
            ['label' => 'Color', 'value' => 'color', 'color' => '#00ff00', 'default' => false],
            ['label' => 'Plain', 'value' => 'plain', 'default' => false],
        ],
    ]);

    $options = $field->uiControl(new FieldContext('value'))->props()['options'];

    expect(array_map(fn (array $option): array => array_diff_key($option, ['value' => true]), $options))->toEqual([
        ['label' => 'Icon', 'icon' => 'star', 'color' => '#ff0000'],
        ['label' => 'Color', 'color' => '#00ff00'],
        ['label' => 'Plain'],
    ]);
})->with([
    'checkboxes' => Checkboxes::class,
]);
