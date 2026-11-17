<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\RadioButtons;

test('option fields accept valid options', function (string $handle, string $fieldType, mixed $value) {
    $result = EntryModel::factory()
        ->withField($handle, $fieldType, [
            'customOptions' => true,
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
            ],
        ], value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has($handle))->toBeFalse();
})->with([
    ['radioButtonsField', RadioButtons::class, 'a'],
    ['checkboxesField', Checkboxes::class, ['a']],
]);

test('option fields reject values that aren’t options without custom options', function (string $handle, string $fieldType, mixed $value) {
    $result = EntryModel::factory()
        ->withField($handle, $fieldType, [
            'customOptions' => false,
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
            ],
        ], value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has($handle))->toBeTrue();
})->with([
    ['radioButtonsField', RadioButtons::class, 'b'],
    ['checkboxesField', Checkboxes::class, ['b']],
]);

test('option fields accept custom values with custom options', function (string $handle, string $fieldType, mixed $value) {
    $result = EntryModel::factory()
        ->withField($handle, $fieldType, [
            'customOptions' => true,
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
            ],
        ], value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has($handle))->toBeFalse();
})->with([
    ['radioButtonsField', RadioButtons::class, 'b'],
    ['checkboxesField', Checkboxes::class, ['a', 'b']],
]);
