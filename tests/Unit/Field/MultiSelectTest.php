<?php

declare(strict_types=1);

use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\MultiSelect;

it('offers its options in a multiple-choice combobox holding every selected value', function () {
    $field = new MultiSelect([
        'options' => [
            ['label' => 'A', 'value' => 'a', 'default' => false],
            ['label' => 'B', 'value' => 'b', 'default' => false],
            ['label' => 'C', 'value' => 'c', 'default' => false],
        ],
    ]);

    $control = $field->uiControl(new FieldContext('value', $field->normalizeValue(['a', 'c'], null)));

    expect($control->component())->toBe('craft:combobox')
        ->and($control->props())->toMatchArray(['multiple' => true, 'requireOptionMatch' => true])
        ->and($control->getValue())->toBe(['base64:'.base64_encode('a'), 'base64:'.base64_encode('c')]);
});

it('passes option groups, icons and colors to the combobox, with a hash on each color', function () {
    $field = new MultiSelect([
        'options' => [
            ['label' => 'Plain', 'value' => 'plain', 'default' => false],
            ['optgroup' => 'Group'],
            ['label' => 'Icon', 'value' => 'icon', 'icon' => 'star', 'color' => 'ff0000', 'default' => false],
            ['label' => 'Color', 'value' => 'color', 'color' => '00ff00', 'default' => false],
        ],
    ]);

    $encode = fn (string $value): string => 'base64:'.base64_encode($value);

    expect($field->uiControl(new FieldContext('value'))->props()['options'])->toBe([
        ['label' => 'Plain', 'value' => $encode('plain')],
        ['type' => 'optgroup', 'label' => 'Group', 'options' => [
            ['label' => 'Icon', 'value' => $encode('icon'), 'data' => ['icon' => 'star', 'color' => '#ff0000']],
            ['label' => 'Color', 'value' => $encode('color'), 'data' => ['indicator' => ['fill' => '#00ff00']]],
        ]],
    ]);
});
