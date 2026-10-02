<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Support\Facades\Elements;

test('valid scalar field values pass element validation', function (string $handle, string $fieldType, array $settings, mixed $value) {
    $result = EntryModel::factory()
        ->withField($handle, $fieldType, $settings, value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has($handle))->toBeFalse();
})->with([
    ['emailField', Email::class, [], 'dev@example.com'],
    ['numberField', Number::class, ['min' => 5, 'max' => 10], 7],
    ['rangeField', Range::class, ['min' => 1, 'max' => 5], 3],
    ['dateField', Date::class, ['min' => '2020-01-01'], new DateTime('2020-01-02')],
    ['timeField', Time::class, ['min' => '09:00', 'max' => '17:00'], '10:00'],
    ['moneyField', Money::class, ['currency' => 'USD', 'min' => 10000, 'max' => 50000], ['currency' => 'USD', 'value' => 300]],
    ['jsonField', Json::class, [], new JsonData(['foo' => 'bar'])],
    ['plainTextField', PlainText::class, ['byteLimit' => 4], 'Okay'],
    ['colorField', Color::class, ['allowCustomColors' => false, 'palette' => [['color' => '#000000']]], '#000000'],
]);

test('invalid scalar field values add element errors', function (string $handle, string $fieldType, array $settings, mixed $value) {
    $result = EntryModel::factory()
        ->withField($handle, $fieldType, $settings, value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has($handle))->toBeTrue();
})->with([
    ['emailField', Email::class, [], 'not-an-email'],
    ['numberField', Number::class, ['min' => 5, 'max' => 10], 2],
    ['rangeField', Range::class, ['min' => 1, 'max' => 5], 7],
    ['dateField', Date::class, ['min' => '2020-01-02'], new DateTime('2020-01-01')],
    ['timeField', Time::class, ['min' => '09:00', 'max' => '17:00'], '08:00'],
    ['moneyField', Money::class, ['currency' => 'USD', 'min' => 10000, 'max' => 50000], ['currency' => 'USD', 'value' => 50]],
    ['jsonField', Json::class, [], new Json()->normalizeValueFromRequest('{invalid', null)],
    ['plainTextField', PlainText::class, ['byteLimit' => 2], 'Too long'],
    ['colorField', Color::class, ['allowCustomColors' => false, 'palette' => [['color' => '#000000']]], '#ffffff'],
]);

test('JSON request values preserve user data and reject malformed input', function (string $raw, bool $valid) {
    $result = EntryModel::factory()
        ->withField('jsonField', Json::class, value: new JsonData(['existing' => 'value']))
        ->createElementWithFields();
    $entry = $result->element;
    $field = $entry->getFieldLayout()->getFieldByHandle('jsonField');

    $entry->setFieldValueFromRequest('jsonField', $raw);
    $value = $entry->getFieldValue('jsonField');
    $control = $field->formControl(new FieldContext('jsonField', value: $value, element: $entry));

    if ($valid) {
        expect(json_decode($control->getValue(), true, flags: JSON_THROW_ON_ERROR))
            ->toBe(json_decode($raw, true, flags: JSON_THROW_ON_ERROR));
    } else {
        expect($control->getValue())->toBe($raw);
        expect($value->getJson())->toBe($raw);
    }

    expect(Elements::saveElement($entry))->toBe($valid);
    expect($entry->errors()->has('jsonField'))->toBe(! $valid);
    $stored = Entry::find()->id($entry->id)->status(null)->one()->getFieldValue('jsonField');
    expect($stored?->getValue())->toBe($valid ? json_decode($raw, true, flags: JSON_THROW_ON_ERROR) : ['existing' => 'value']);
})->with([
    'error key' => ['{"__ERROR__":"user data"}', true],
    'value key' => ['{"__VALUE__":"user data"}', true],
    'both keys' => ['{"__ERROR__":true,"__VALUE__":123}', true],
    'string' => ['"text"', true],
    'number' => ['42', true],
    'boolean' => ['false', true],
    'null' => ['null', true],
    'malformed' => [" \n{\"broken\":\t}\n ", false],
]);
