<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\FieldContext;

function encodedDropdownValue(string $value): string
{
    return 'base64:'.base64_encode($value);
}

it('offers options with icons or colors in a combobox, grouped as in its settings', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'Plain', 'value' => 'plain', 'default' => false],
            ['optgroup' => 'Group'],
            ['label' => 'Icon', 'value' => 'icon', 'icon' => 'star', 'color' => 'ff0000', 'default' => false],
            ['label' => 'Color', 'value' => 'color', 'color' => '00ff00', 'default' => false],
        ],
    ]);

    $control = $field->uiControl(new FieldContext('value', $field->normalizeValue(null, null)));

    expect($control->component())->toBe('craft:combobox')
        ->and($control->props())->toMatchArray(['requireOptionMatch' => true, 'clearable' => true])
        ->and($control->props())->not->toHaveKey('multiple')
        ->and($control->props()['options'])->toBe([
            ['label' => 'Plain', 'value' => encodedDropdownValue('plain')],
            ['type' => 'optgroup', 'label' => 'Group', 'options' => [
                ['label' => 'Icon', 'value' => encodedDropdownValue('icon'), 'data' => ['icon' => 'star', 'color' => '#ff0000']],
                ['label' => 'Color', 'value' => encodedDropdownValue('color'), 'data' => ['indicator' => ['fill' => '#00ff00']]],
            ]],
        ])
        ->and($control->getValue())->toBeNull();
});

it('keeps a combobox’s own blank option selectable instead of clearable', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'None', 'value' => '', 'default' => false],
            ['label' => 'Icon', 'value' => 'icon', 'icon' => 'star', 'default' => false],
        ],
    ]);

    $control = $field->uiControl(new FieldContext('value', $field->normalizeValue('', null)));

    expect($control->component())->toBe('craft:combobox')
        ->and($control->props())->not->toHaveKey('clearable')
        ->and($control->props()['options'][0])->toBe(['label' => 'None', 'value' => '__blank__'])
        ->and($control->getValue())->toBe('__blank__');
});

it('keeps plain options in a select, grouped as in its settings', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'Plain', 'value' => 'plain', 'default' => false],
            ['optgroup' => 'Group'],
            ['label' => 'Grouped', 'value' => 'grouped', 'default' => false],
        ],
    ]);

    $control = $field->uiControl(new FieldContext('value'));

    expect($control->component())->toBe('craft:choice')
        ->and($control->props()['presentation'])->toBe('select')
        ->and($control->props())->not->toHaveKey('placeholder')
        ->and($control->props()['options'])->toEqual([
            ['label' => 'Plain', 'value' => encodedDropdownValue('plain')],
            ['label' => 'Grouped', 'value' => encodedDropdownValue('grouped'), 'group' => 'Group'],
        ]);
});
