<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Support\DateTimeHelper;

it('projects the complete Plain Text settings surface', function () {
    $field = new PlainText([
        'uiMode' => 'enlarged',
        'placeholder' => 'Summary',
        'charLimit' => 120,
        'code' => true,
        'multiline' => true,
        'initialRows' => 6,
    ]);

    expect($field->getSettings())->toMatchArray([
        'uiMode' => 'enlarged',
        'placeholder' => 'Summary',
        'charLimit' => 120,
        'byteLimit' => null,
        'code' => true,
        'multiline' => true,
        'initialRows' => 6,
    ])->and($field->getSettingsFormDefinition(false)?->toArray())->toBe([
        'elements' => [
            [
                'type' => 'craft:field',
                'props' => [
                    'label' => 'UI Mode',
                    'instructions' => 'How the field should be presented in the control panel.',
                ],
                'children' => [[
                    'type' => 'craft:select-input',
                    'name' => 'uiMode',
                    'props' => [
                        'options' => [
                            ['label' => 'Normal', 'value' => 'normal'],
                            ['label' => 'Enlarged', 'value' => 'enlarged'],
                        ],
                    ],
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => [
                    'label' => 'Placeholder Text',
                    'instructions' => 'The text that will be shown if the field doesn’t have a value.',
                ],
                'children' => [[
                    'type' => 'craft:text-input',
                    'name' => 'placeholder',
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => [
                    'label' => 'Character Limit',
                    'instructions' => 'The maximum number of characters the field is allowed to have.',
                ],
                'children' => [[
                    'type' => 'craft:number-input',
                    'name' => 'charLimit',
                    'props' => ['type' => 'number', 'min' => 1],
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => [
                    'label' => 'Byte Limit',
                    'instructions' => 'The maximum number of bytes the field is allowed to have.',
                ],
                'children' => [[
                    'type' => 'craft:number-input',
                    'name' => 'byteLimit',
                    'props' => ['type' => 'number', 'min' => 1],
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => ['label' => 'Use a monospaced font'],
                'children' => [[
                    'type' => 'craft:lightswitch-input',
                    'name' => 'code',
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => ['label' => 'Allow line breaks'],
                'children' => [[
                    'type' => 'craft:lightswitch-input',
                    'name' => 'multiline',
                ]],
            ],
            [
                'type' => 'craft:field',
                'props' => ['label' => 'Initial Rows'],
                'children' => [[
                    'type' => 'craft:number-input',
                    'name' => 'initialRows',
                    'props' => ['type' => 'number', 'min' => 1],
                ]],
                'visibleWhen' => [
                    'name' => 'multiline',
                    'operator' => 'equals',
                    'value' => true,
                ],
            ],
        ],
    ]);
});

it('projects the complete Number settings surface', function () {
    $field = new Number([
        'min' => -10,
        'max' => 100,
        'step' => 0.5,
        'decimals' => 2,
        'size' => 8,
        'defaultValue' => 12.5,
        'prefix' => '$',
        'suffix' => ' USD',
        'previewFormat' => Number::FORMAT_CURRENCY,
        'previewCurrency' => 'USD',
    ]);

    $settings = $field->getSettings();
    $inputs = projectedInputs($field->getSettingsFormDefinition(false)?->toArray());

    expect($settings)->toMatchArray([
        'min' => -10,
        'max' => 100,
        'step' => 0.5,
        'decimals' => 2,
        'size' => 8,
        'defaultValue' => 12.5,
        'prefix' => '$',
        'suffix' => ' USD',
        'previewFormat' => Number::FORMAT_CURRENCY,
        'previewCurrency' => 'USD',
    ])->and(array_keys($inputs))->toBe([
        'min',
        'max',
        'step',
        'decimals',
        'size',
        'defaultValue',
        'prefix',
        'suffix',
        'previewFormat',
        'previewCurrency',
    ])->and($inputs['min'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Min Value',
    ])->and($inputs['max'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Max Value',
    ])->and($inputs['step'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Step Size',
    ])->and($inputs['decimals'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Decimal Points',
        'props' => ['type' => 'number', 'min' => 0],
    ])->and($inputs['size'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Size',
        'props' => ['type' => 'number', 'min' => 1],
    ])->and($inputs['defaultValue'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Default Value',
    ])->and($inputs['prefix'])->toMatchArray([
        'type' => 'craft:text-input',
        'label' => 'Prefix Text',
        'instructions' => 'Text that should be shown before the input.',
    ])->and($inputs['suffix'])->toMatchArray([
        'type' => 'craft:text-input',
        'label' => 'Suffix Text',
        'instructions' => 'Text that should be shown after the input.',
    ])->and($inputs['previewFormat'])->toMatchArray([
        'type' => 'craft:select-input',
        'label' => 'Preview Format',
        'instructions' => 'How field values will be formatted within element indexes.',
        'props' => ['options' => [
            ['label' => 'As decimal numbers', 'value' => Number::FORMAT_DECIMAL],
            ['label' => 'As currency values', 'value' => Number::FORMAT_CURRENCY],
            ['label' => 'Unformatted', 'value' => Number::FORMAT_NONE],
        ]],
    ])->and($inputs['previewCurrency'])->toMatchArray([
        'type' => 'craft:select-input',
        'label' => 'Currency',
        'visibleWhen' => [
            'name' => 'previewFormat',
            'operator' => 'equals',
            'value' => Number::FORMAT_CURRENCY,
        ],
    ])->and($inputs['previewCurrency']['props']['options'][0])->toBe([
        'label' => 'Choose a currency…',
        'value' => null,
    ]);
});

it('projects the complete Date settings surface', function () {
    $field = new Date([
        'showDate' => true,
        'showTime' => true,
        'showTimeZone' => true,
        'minuteIncrement' => 15,
        'min' => '2026-01-02',
        'max' => '2026-12-31',
    ]);

    $settings = $field->getSettings();
    $inputs = projectedInputs($field->getSettingsFormDefinition(false)?->toArray());

    expect($settings)->toMatchArray([
        'showDate' => true,
        'showTime' => true,
        'showTimeZone' => true,
        'minuteIncrement' => 15,
    ])->and($settings['min'])->toBe(DateTimeHelper::toIso8601($field->min))
        ->and($settings['max'])->toBe(DateTimeHelper::toIso8601($field->max))
        ->and(array_keys($inputs))->toBe([
            'showDate',
            'showTime',
            'minuteIncrement',
            'showTimeZone',
            'min',
            'max',
        ])->and($inputs['showDate'])->toMatchArray([
            'type' => 'craft:lightswitch-input',
            'label' => 'Show date',
        ])->and($inputs['showTime'])->toMatchArray([
            'type' => 'craft:lightswitch-input',
            'label' => 'Show time',
            'tip' => 'Time fields are better suited for managing Time-only values.',
        ])->and($inputs['minuteIncrement'])->toMatchArray([
            'type' => 'craft:select-input',
            'label' => 'Minute Increment',
            'instructions' => 'The number of minutes that timepicker options should be incremented by. (Authors can enter a specific time manually.)',
            'props' => ['options' => [
                ['label' => '5', 'value' => 5],
                ['label' => '10', 'value' => 10],
                ['label' => '15', 'value' => 15],
                ['label' => '30', 'value' => 30],
                ['label' => '60', 'value' => 60],
            ]],
            'visibleWhen' => [
                'name' => 'showTime',
                'operator' => 'equals',
                'value' => true,
            ],
        ])->and($inputs['showTimeZone'])->toMatchArray([
            'type' => 'craft:lightswitch-input',
            'label' => 'Show Time Zone',
            'instructions' => 'Whether authors should be able to choose which time zone the time is in.',
            'visibleWhen' => [
                'name' => 'showTime',
                'operator' => 'equals',
                'value' => true,
            ],
        ])->and($inputs['min'])->toMatchArray([
            'type' => 'craft:date-input',
            'label' => 'Min Date',
            'visibleWhen' => [
                'name' => 'showDate',
                'operator' => 'equals',
                'value' => true,
            ],
        ])->and($inputs['max'])->toMatchArray([
            'type' => 'craft:date-input',
            'label' => 'Max Date',
            'visibleWhen' => [
                'name' => 'showDate',
                'operator' => 'equals',
                'value' => true,
            ],
        ]);
});

it('projects the complete Time settings surface', function () {
    $field = new Time([
        'minuteIncrement' => 10,
        'min' => '08:30',
        'max' => '17:45',
    ]);

    $inputs = projectedInputs($field->getSettingsFormDefinition(true)?->toArray());

    expect($field->getSettings())->toMatchArray([
        'minuteIncrement' => 10,
        'min' => '08:30',
        'max' => '17:45',
    ])->and(array_keys($inputs))->toBe([
        'minuteIncrement',
        'min',
        'max',
    ])->and($inputs['minuteIncrement'])->toMatchArray([
        'type' => 'craft:select-input',
        'label' => 'Minute Increment',
        'readOnly' => true,
    ])->and($inputs['min'])->toMatchArray([
        'type' => 'craft:time-input',
        'label' => 'Min Time',
        'readOnly' => true,
    ])->and($inputs['max'])->toMatchArray([
        'type' => 'craft:time-input',
        'label' => 'Max Time',
        'readOnly' => true,
    ]);
});

it('projects the complete Lightswitch settings surface', function () {
    $field = new Lightswitch([
        'default' => true,
        'offLabel' => 'Private',
        'onLabel' => 'Public',
        'showLabelsInCards' => true,
    ]);

    $inputs = projectedInputs($field->getSettingsFormDefinition(true)?->toArray());

    expect(array_keys($inputs))->toBe([
        'default',
        'offLabel',
        'onLabel',
        'showLabelsInCards',
    ])->and($inputs['default'])->toMatchArray([
        'type' => 'craft:lightswitch-input',
        'label' => 'Default Value',
        'readOnly' => true,
    ])->and($inputs['offLabel'])->toMatchArray([
        'type' => 'craft:text-input',
        'label' => 'OFF Label',
        'instructions' => 'The label text to display beside the lightswitch’s disabled state.',
        'readOnly' => true,
    ])->and($inputs['onLabel'])->toMatchArray([
        'type' => 'craft:text-input',
        'label' => 'ON Label',
        'instructions' => 'The label text to display beside the lightswitch’s enabled state.',
        'readOnly' => true,
    ])->and($inputs['showLabelsInCards'])->toMatchArray([
        'type' => 'craft:lightswitch-input',
        'label' => 'Show ON/OFF labels in cards',
        'instructions' => 'Whether card views which include this field should show the custom ON/OFF labels, rather than the field name.',
        'readOnly' => true,
    ]);
});

it('projects the complete Icon settings surface without HTML', function () {
    $field = new Icon([
        'includeProIcons' => true,
        'fullGraphqlData' => false,
    ]);

    $inputs = projectedInputs($field->getSettingsFormDefinition(false)?->toArray());
    $json = json_encode($inputs, JSON_THROW_ON_ERROR);

    expect(array_keys($inputs))->toBe([
        'includeProIcons',
        'fullGraphqlData',
    ])->and($inputs['includeProIcons'])->toMatchArray([
        'type' => 'craft:lightswitch-input',
        'label' => 'Include Pro icons',
        'instructions' => 'Should icons that are exclusive to Font Awesome Pro be selectable?',
        'tip' => 'View Font Awesome Pro pricing: https://fontawesome.com/plans',
    ])->and($inputs['fullGraphqlData'])->toMatchArray([
        'type' => 'craft:select-input',
        'label' => 'GraphQL Mode',
        'props' => ['options' => [
            ['label' => 'Full data', 'value' => true],
            ['label' => 'Name only', 'value' => false],
        ]],
    ])->and($json)->not->toContain('<a')->not->toContain('<script');
});

it('projects the complete Email settings surface', function () {
    $field = new Email(['placeholder' => 'name@example.com']);

    expect(projectedInputs($field->getSettingsFormDefinition(false)?->toArray()))->toBe([
        'placeholder' => [
            'type' => 'craft:text-input',
            'label' => 'Placeholder Text',
            'instructions' => 'The text that will be shown if the field doesn’t have a value.',
        ],
    ]);
});

it('returns no definition for scalar fields without settings', function () {
    expect(new Country()->getSettingsFormDefinition(false))->toBeNull()
        ->and(new Json()->getSettingsFormDefinition(false))->toBeNull();
});

it('projects the complete Color settings surface', function () {
    $field = new Color([
        'palette' => [
            ['color' => '#ff0000', 'label' => 'Red', 'default' => true],
            ['color' => '#0000ff', 'label' => null, 'default' => false],
        ],
        'allowCustomColors' => true,
    ]);

    $inputs = projectedInputs($field->getSettingsFormDefinition(true)?->toArray());

    expect($field->getSettings())->toMatchArray([
        'palette' => [
            ['color' => '#ff0000', 'label' => 'Red', 'default' => true],
            ['color' => '#0000ff', 'label' => null, 'default' => false],
        ],
        'allowCustomColors' => true,
    ])->and($inputs)->toBe([
        'palette' => [
            'type' => 'craft:color-palette-input',
            'label' => 'Palette',
            'instructions' => 'Define the available colors to choose from.',
            'readOnly' => true,
        ],
        'allowCustomColors' => [
            'type' => 'craft:lightswitch-input',
            'label' => 'Allow custom colors',
            'readOnly' => true,
        ],
    ]);
});

it('projects the complete Money settings surface with currency subunits', function () {
    $field = new Money([
        'currency' => 'USD',
        'defaultValue' => 1234,
        'min' => 100,
        'max' => 10000,
        'showCurrency' => false,
        'size' => 10,
    ]);

    $inputs = projectedInputs($field->getSettingsFormDefinition(false)?->toArray());

    expect($field->getSettings())->toMatchArray([
        'currency' => 'USD',
        'defaultValue' => 1234,
        'min' => 100,
        'max' => 10000,
        'showCurrency' => false,
        'size' => 10,
    ])->and(array_keys($inputs))->toBe([
        'currency',
        'defaultValue',
        'min',
        'max',
        'showCurrency',
        'size',
    ])->and($inputs['currency'])->toMatchArray([
        'type' => 'craft:select-input',
        'label' => 'Currency',
        'required' => true,
    ])->and($inputs['currency']['props']['options'])->toContain([
        'label' => 'USD',
        'value' => 'USD',
    ])->and($inputs['defaultValue'])->toMatchArray([
        'type' => 'craft:money-input',
        'label' => 'Default Value',
        'props' => ['fractionDigits' => 2, 'minorUnits' => true],
    ])->and($inputs['min'])->toMatchArray([
        'type' => 'craft:money-input',
        'label' => 'Min Value',
        'props' => ['fractionDigits' => 2, 'minorUnits' => true],
    ])->and($inputs['max'])->toMatchArray([
        'type' => 'craft:money-input',
        'label' => 'Max Value',
        'props' => ['fractionDigits' => 2, 'minorUnits' => true],
    ])->and($inputs['showCurrency'])->toMatchArray([
        'type' => 'craft:lightswitch-input',
        'label' => 'Show Currency',
    ])->and($inputs['size'])->toMatchArray([
        'type' => 'craft:number-input',
        'label' => 'Size',
        'props' => ['type' => 'number', 'min' => 1],
    ]);
});

function projectedInputs(?array $definition): array
{
    $inputs = [];

    foreach ($definition['elements'] ?? [] as $field) {
        $input = $field['children'][0];
        $inputs[$input['name']] = array_filter([
            'type' => $input['type'],
            'label' => $field['props']['label'] ?? null,
            'instructions' => $field['props']['instructions'] ?? null,
            'tip' => $field['props']['tip'] ?? null,
            'warning' => $field['props']['warning'] ?? null,
            'required' => $field['props']['required'] ?? null,
            'readOnly' => $field['props']['readOnly'] ?? null,
            'props' => $input['props'] ?? null,
            'visibleWhen' => $field['visibleWhen'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    return $inputs;
}
