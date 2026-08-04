<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Table;

test('table field validates column values', function () {
    $settings = [
        'columns' => [
            'col1' => [
                'heading' => 'Email',
                'handle' => 'email',
                'type' => 'email',
            ],
        ],
    ];

    $invalidResult = EntryModel::factory()
        ->withField('tableField', Table::class, $settings, value: [['col1' => 'invalid']])
        ->createElementWithFields(save: false);
    $invalidResult->element->validate();

    expect($invalidResult->element->errors()->has('tableField'))->toBeTrue();

    $validResult = EntryModel::factory()
        ->withField('tableFieldValid', Table::class, $settings, value: [['col1' => 'dev@example.com']])
        ->createElementWithFields(save: false);
    $validResult->element->validate();

    expect($validResult->element->errors()->has('tableFieldValid'))->toBeFalse();
});
