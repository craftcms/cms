<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Arr;

test('toArray', function (array $expected, mixed $object) {
    expect(Arr::toArray($object))->toBe($expected);
})->with(function () {
    $stdClass2 = new stdClass;
    $stdClass2->subProp = 'value';

    $stdClass = new stdClass;
    $stdClass->prop1 = '11';
    $stdClass->prop2 = '22';
    $stdClass->prop3 = $stdClass2;

    return [
        [[], null],
        [[], null], [[1, 2, 3], [1, 2, 3]],
        [['prop1' => '11', 'prop2' => '22', 'prop3' => ['subProp' => 'value']], $stdClass],
        [['foo', 'bar, baz'], 'foo, bar\, baz', ''],
    ];
});

test('merge', function () {
    $a = [
        'name' => 'Yii',
        'version' => '1.0',
        'options' => [
            'namespace' => false,
            'unittest' => false,
        ],
        'features' => [
            'mvc',
        ],
    ];
    $b = [
        'version' => '1.1',
        'options' => [
            'unittest' => true,
        ],
        'features' => [
            'gii',
        ],
    ];
    $c = [
        'version' => '2.0',
        'options' => [
            'namespace' => true,
        ],
        'features' => [
            'debug',
        ],
        'foo',
    ];

    $result = Arr::merge($a, $b, $c);
    $expected = [
        'name' => 'Yii',
        'version' => '2.0',
        'options' => [
            'namespace' => true,
            'unittest' => true,
        ],
        'features' => [
            'mvc',
            'gii',
            'debug',
        ],
        'foo',
    ];

    expect($expected)->toBe($result);
});

test('whereNotEmpty', function () {
    expect(
        Arr::whereNotEmpty([0 => 1, 1 => 2, 3 => '', 4 => null, 5 => 5]),
    )->toBe(
        [0 => 1, 1 => 2, 4 => null, 5 => 5]
    );
});

test('first', function (mixed $expected, array $array) {
    expect(Arr::first($array))->toBe($expected);
})->with(function () {
    $std = new stdClass;
    $std->a = '22';

    return [
        ['test', ['test']],
        [['test'], [['test']]],
        [$std, ['key' => $std]],
    ];
});

test('except', function (array $expected, array $array, string $key) {
    expect(Arr::except($array, $key))->toBe($expected);
})->with([
    [[], ['key' => 'value'], 'key'],
    [['key' => 'value'], ['key' => 'value', 'key2' => 'value2'], 'key2'],
    [['key' => 'value'], ['key' => 'value'], 'notakey'],
]);

test('get', function (string $expected, array $array, string $key) {
    expect(Arr::get($array, $key))->toBe($expected);
})->with([
    ['foo', ['foo' => 'foo'], 'foo'],
    ['foo.bar', ['foo' => ['bar' => 'foo.bar']], 'foo[bar]'],
    ['foo.bar.baz', ['foo' => ['bar' => ['baz' => 'foo.bar.baz']]], 'foo[bar][baz]'],
    ['foo[bar', ['foo[bar' => 'foo[bar'], 'foo[bar'],
    ['foo[bar][]', ['foo[bar][]' => 'foo[bar][]'], 'foo[bar][]'],
    ['foo.bar:baz.qux', ['foo' => ['bar:baz' => ['qux' => 'foo.bar:baz.qux']]], 'foo[bar:baz][qux]'],
    ['foo-bar.baz.qux', ['foo-bar' => ['baz' => ['qux' => 'foo-bar.baz.qux']]], 'foo-bar[baz][qux]'],
]);

test('isOrdered', function (bool $expected, array $array) {
    expect(Arr::isOrdered($array))->toBe($expected);
})->with([
    [true, ['a', 'b', 'c']],
    [true, [5 => 'a', 10 => 'b', 15 => 'c']],
    [false, ['a' => 1, 'b' => 2, 'c' => 3]],
    [false, ['a', 'b', 'c' => 3]],
    [false, [3 => 'a', 2 => 'b', 1 => 'c']],
]);

test('isNumeric', function (bool $expected, array $array) {
    expect(Arr::isNumeric($array))->toBe($expected);
})->with([
    [true, [0, 1, 2, '3']],
    [false, [0, 1, 2, '3a']],
]);

test('isIndexed', function (bool $expected, array $array) {
    expect(Arr::isIndexed($array))->toBe($expected);
})->with([
    [true, [0, 1, 2]],
    [false, [0, 1, 2, '3']],
    [false, [0, 1, 2, '3a']],
]);
