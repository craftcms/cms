<?php

declare(strict_types=1);

use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\RadioButtons;

it('projects every option field setting through native form elements', function (
    Closure $makeField,
    string $optionLabel,
    array $optionProps,
    array $additionalInputs,
) {
    $options = [
        [
            'label' => 'Breaking News',
            'value' => 'breakingNews',
            'icon' => 'newspaper',
            'color' => 'ff0000',
            'default' => true,
        ],
        [
            'label' => 'Opinion',
            'value' => 'opinion',
            'icon' => 'comment',
            'color' => '0000ff',
            'default' => false,
        ],
    ];
    /** @var BaseOptionsField $field */
    $field = $makeField($options);

    $definition = $field->getSettingsForm(true)->toArray();

    expect($field->options)->toBe($options)
        ->and($definition)->not->toBeNull()
        ->and(array_map(
            fn (array $element): string => $element['children'][0]['name'],
            $definition['elements'],
        ))->toBe(['options', ...$additionalInputs])
        ->and($definition['elements'][0])->toMatchArray([
            'type' => 'craft:field',
            'props' => [
                'label' => $optionLabel,
                'instructions' => 'Define the available options.',
                'readOnly' => true,
            ],
            'children' => [[
                'type' => 'craft:option-rows',
                'name' => 'options',
                'props' => $optionProps,
            ]],
        ]);

    foreach ($definition['elements'] as $element) {
        expect($element['props']['readOnly'])->toBeTrue();
    }
})->with([
    'checkboxes' => [
        fn (array $options): Checkboxes => new Checkboxes(compact('options')),
        'Checkbox Options',
        ['multipleDefaults' => true, 'icons' => true, 'colors' => true],
        ['customOptions'],
    ],
    'radio buttons' => [
        fn (array $options): RadioButtons => new RadioButtons(compact('options')),
        'Radio Button Options',
        ['icons' => true, 'colors' => true],
        ['customOptions'],
    ],
    'dropdown' => [
        fn (array $options): Dropdown => new Dropdown(compact('options')),
        'Dropdown Options',
        ['optgroups' => true, 'icons' => true, 'colors' => true],
        [],
    ],
    'multi-select' => [
        fn (array $options): MultiSelect => new MultiSelect(compact('options')),
        'Multi-select Options',
        ['multipleDefaults' => true, 'optgroups' => true, 'icons' => true, 'colors' => true],
        [],
    ],
    'button group' => [
        fn (array $options): ButtonGroup => new ButtonGroup(compact('options')),
        'Options',
        ['icons' => true],
        ['iconsOnly'],
    ],
]);

it('preserves optgroup rows and generated option values outside the definition', function () {
    $options = [
        ['optgroup' => 'Published'],
        ['label' => 'Breaking News', 'value' => 'breakingNews', 'default' => '1'],
    ];
    $field = new Dropdown(compact('options'));

    expect($field->getSettingsForm(false)->toArray()['elements'][0]['children'][0]['props'])
        ->toBe(['optgroups' => true, 'icons' => true, 'colors' => true])
        ->and($field->options)->toBe($options);
});
