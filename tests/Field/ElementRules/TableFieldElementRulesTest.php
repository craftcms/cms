<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Tests\Support\FieldElementRulesHelper;

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

    [$invalidEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'tableField',
        fieldType: Table::class,
        fieldSettings: $settings,
        value: [['col1' => 'invalid']],
    );
    $invalidEntry->validate();

    expect($invalidEntry->errors()->has('tableField'))->toBeTrue();

    [$validEntry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'tableFieldValid',
        fieldType: Table::class,
        fieldSettings: $settings,
        value: [['col1' => 'dev@example.com']],
    );
    $validEntry->validate();

    expect($validEntry->errors()->has('tableFieldValid'))->toBeFalse();
});
