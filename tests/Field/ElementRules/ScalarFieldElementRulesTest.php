<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Data\JsonData;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Tests\TestClasses\FieldElementRulesHelper;

test('valid scalar field values pass element validation', function (string $handle, string $fieldType, array $settings, mixed $value) {
    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: $handle,
        fieldType: $fieldType,
        fieldSettings: $settings,
        value: $value,
    );

    $entry->validate();

    expect($entry->errors()->has($handle))->toBeFalse();
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
    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: $handle,
        fieldType: $fieldType,
        fieldSettings: $settings,
        value: $value,
    );

    $entry->validate();

    expect($entry->errors()->has($handle))->toBeTrue();
})->with([
    ['emailField', Email::class, [], 'not-an-email'],
    ['numberField', Number::class, ['min' => 5, 'max' => 10], 2],
    ['rangeField', Range::class, ['min' => 1, 'max' => 5], 7],
    ['dateField', Date::class, ['min' => '2020-01-02'], new DateTime('2020-01-01')],
    ['timeField', Time::class, ['min' => '09:00', 'max' => '17:00'], '08:00'],
    ['moneyField', Money::class, ['currency' => 'USD', 'min' => 10000, 'max' => 50000], ['currency' => 'USD', 'value' => 50]],
    ['jsonField', Json::class, [], new JsonData(['__ERROR__' => 'Invalid JSON'])],
    ['plainTextField', PlainText::class, ['byteLimit' => 2], 'Too long'],
    ['colorField', Color::class, ['allowCustomColors' => false, 'palette' => [['color' => '#000000']]], '#ffffff'],
]);
