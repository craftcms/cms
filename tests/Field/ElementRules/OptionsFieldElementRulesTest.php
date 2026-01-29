<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Tests\TestClasses\FieldElementRulesHelper;

test('option fields accept valid options', function (string $handle, string $fieldType, mixed $value) {
    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: $handle,
        fieldType: $fieldType,
        fieldSettings: [
            'customOptions' => true,
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
            ],
        ],
        value: $value,
    );

    $entry->validate();

    expect($entry->errors()->has($handle))->toBeFalse();
})->with([
    ['radioButtonsField', RadioButtons::class, 'a'],
    ['checkboxesField', Checkboxes::class, ['a']],
]);

test('option fields reject invalid options', function (string $handle, string $fieldType, mixed $value) {
    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: $handle,
        fieldType: $fieldType,
        fieldSettings: [
            'customOptions' => true,
            'options' => [
                ['label' => 'Option A', 'value' => 'a'],
            ],
        ],
        value: $value,
    );

    $entry->validate();

    expect($entry->errors()->has($handle))->toBeTrue();
})->with([
    ['radioButtonsField', RadioButtons::class, 'b'],
    ['checkboxesField', Checkboxes::class, ['b']],
]);
