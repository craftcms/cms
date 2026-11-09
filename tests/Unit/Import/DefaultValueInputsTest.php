<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DefaultValueInputs;

it('enters a property’s default with the input its type calls for', function (array $types, ?array $expected) {
    expect(DefaultValueInputs::forPropertyTypes($types))->toBe($expected);
})->with([
    'date' => [[DateTime::class, 'null'], ['type' => 'datetime']],
    'bool' => [['bool'], ['type' => 'lightswitch']],
    'int' => [['int', 'null'], ['type' => 'number']],
    'string' => [['string'], ['type' => 'text']],
    'array' => [['array'], null],
]);

it('enters a database column’s default with the input its type calls for', function (string $typeName, string $type) {
    expect(DefaultValueInputs::forColumnType($typeName))->toBe(['type' => $type]);
})->with([
    ['bigint', 'number'],
    ['decimal', 'number'],
    ['boolean', 'lightswitch'],
    ['date', 'date'],
    ['timestamp', 'datetime'],
    ['varchar', 'text'],
]);
