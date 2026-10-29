<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;

test('an option saved without a value is treated as a blank option', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'None', 'default' => ''],
            ['label' => 'A', 'value' => 'a', 'default' => ''],
        ],
    ]);

    expect($field->options[0]['value'])->toBe('')
        ->and($field->normalizeValue(null, null)->value)->toBe('');
});

test('a blank option value posted as null is saved as an empty string', function () {
    $fields = app(Fields::class);

    $fields->saveField($field = $fields->createField([
        'type' => Dropdown::class,
        'name' => 'Dropdown',
        'handle' => 'dropdown',
        'options' => [
            ['label' => 'None', 'value' => null, 'default' => ''],
            ['label' => 'A', 'value' => 'a', 'default' => ''],
        ],
    ]));

    $fields->refreshFields();

    expect($fields->getFieldById($field->id)->getSettings()['options'][0]['value'])->toBe('');
});

test('a dropdown with its own blank option does not get another one', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'Please select', 'value' => '', 'default' => ''],
            ['label' => 'A', 'value' => 'a', 'default' => ''],
        ],
    ]);

    expect($field->uiControl(new FieldContext('value'))->props()['placeholder'])->toBeFalse();
});

test('a dropdown without a blank option keeps the leading blank', function () {
    $field = new Dropdown([
        'options' => [
            ['label' => 'A', 'value' => 'a', 'default' => ''],
        ],
    ]);

    expect($field->uiControl(new FieldContext('value'))->props())->not->toHaveKey('placeholder');
});
