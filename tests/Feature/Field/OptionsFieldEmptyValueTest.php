<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

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

test('an empty required dropdown selects its empty state, not its first option', function (array $options, string $selectedValue) {
    $field = new Dropdown(['options' => $options]);
    $control = Field::make('Position', $field->uiControl(new FieldContext('position', $field->normalizeValue(null, null))))
        ->required();
    $payload = app(UiResolver::class)->resolve(Ui::make([$control]), new UiContext(namespace: 'fields'));
    $selected = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('select option[selected]');

    expect($selected)->toHaveCount(1)
        ->and($selected->attr('value'))->toBe($selectedValue);
})->with([
    'without a blank option' => [[
        ['label' => 'Left', 'value' => 'left', 'default' => ''],
        ['label' => 'Center', 'value' => 'center', 'default' => ''],
    ], ''],
    'with a blank option' => [[
        ['label' => 'Left', 'value' => 'left', 'default' => ''],
        ['label' => 'None', 'value' => '', 'default' => ''],
    ], '__blank__'],
]);
