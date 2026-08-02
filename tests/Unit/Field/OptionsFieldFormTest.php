<?php

declare(strict_types=1);

use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Http\ViewModels\FieldEditViewModel;
use CraftCms\Cms\Support\Html;

it('projects every option field setting through native form elements', function (
    Closure $makeField,
    string $optionLabel,
    array $columnKeys,
    bool $radioMode,
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
        ])
        ->and($definition['elements'][0]['children'][0])->toMatchArray([
            'type' => 'craft:editable-table-input',
            'name' => 'options',
        ])
        ->and($definition['elements'][0]['children'][0]['props']['addRowLabel'])->toBe('Add an option')
        ->and(array_column($definition['elements'][0]['children'][0]['props']['columns'], 'key'))->toBe($columnKeys)
        ->and($definition['elements'][0]['children'][0]['props']['columns'][array_search('default', $columnKeys, true)]['radioMode'])->toBe($radioMode);

    foreach ($definition['elements'] as $element) {
        expect($element['props']['readOnly'])->toBeTrue();
    }
})->with([
    'checkboxes' => [
        fn (array $options): Checkboxes => new Checkboxes(compact('options')),
        'Checkbox Options',
        ['label', 'value', 'icon', 'color', 'default'],
        false,
        ['customOptions'],
    ],
    'radio buttons' => [
        fn (array $options): RadioButtons => new RadioButtons(compact('options')),
        'Radio Button Options',
        ['label', 'value', 'icon', 'color', 'default'],
        true,
        ['customOptions'],
    ],
    'dropdown' => [
        fn (array $options): Dropdown => new Dropdown(compact('options')),
        'Dropdown Options',
        ['isOptgroup', 'label', 'value', 'icon', 'color', 'default'],
        true,
        [],
    ],
    'multi-select' => [
        fn (array $options): MultiSelect => new MultiSelect(compact('options')),
        'Multi-select Options',
        ['isOptgroup', 'label', 'value', 'icon', 'color', 'default'],
        false,
        [],
    ],
    'button group' => [
        fn (array $options): ButtonGroup => new ButtonGroup(compact('options')),
        'Options',
        ['label', 'value', 'icon', 'default'],
        true,
        ['iconsOnly'],
    ],
]);

it('preserves optgroup rows and generated option values outside the definition', function () {
    $options = [
        ['optgroup' => 'Published'],
        ['label' => 'Breaking News', 'value' => 'breakingNews', 'default' => '1'],
    ];
    $field = new Dropdown(compact('options'));
    $values = new FieldEditViewModel($field, app(Fields::class))->settingsValues();

    expect($field->getSettingsForm(false)->toArray()['elements'][0]['children'][0]['type'])
        ->toBe('craft:editable-table-input')
        ->and($values['types'][Html::id(Dropdown::class)]['options'])->toBe([
            ['isOptgroup' => true, 'label' => 'Published'],
            ['label' => 'Breaking News', 'value' => 'breakingNews', 'default' => '1'],
        ])
        ->and($field->options)->toBe($options);
});
