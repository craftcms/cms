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

test('get preserves non string key behavior', function (mixed $expected, mixed $key, mixed $default = null) {
    $array = [
        'foo' => 'bar',
        0 => 'zero',
        1 => [
            'nested' => 'value',
        ],
    ];

    expect(Arr::get($array, $key, $default))->toBe($expected);
})->with([
    'null key' => [['foo' => 'bar', 0 => 'zero', 1 => ['nested' => 'value']], null],
    'integer key' => ['zero', 0],
    'missing integer key' => ['fallback', 2, 'fallback'],
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

test('containsRecursive', function (bool $expected, array $array, string $key, mixed $value = true, bool $strict = false) {
    expect(Arr::containsRecursive($array, $key, $value, $strict))->toBe($expected);
})->with([
    'matches nested key with default value' => [
        true,
        [
            [
                'rows' => [
                    ['rowId' => '100'],
                ],
            ],
        ],
        'rowId',
    ],
    'matches nested key with loose comparison' => [
        true,
        [
            [
                'rows' => [
                    ['enabled' => 1],
                ],
            ],
        ],
        'enabled',
        true,
    ],
    'does not match nested key with strict comparison' => [
        false,
        [
            [
                'rows' => [
                    ['enabled' => 1],
                ],
            ],
        ],
        'enabled',
        true,
        true,
    ],
    'returns false when key is missing recursively' => [
        false,
        [
            [
                'rows' => [
                    ['label' => 'Row 1'],
                ],
            ],
        ],
        'rowId',
    ],
]);

test('dotifyKey', function (string|int $expected, string|int $string) {
    expect(Arr::dotifyKey($string))->toBe($expected);
})->with([
    ['foo.bar', 'foo[bar]'],
    ['sources.custom:5bb5537d.condition', 'sources[custom:5bb5537d][condition]'],
    ['foo', 'foo'],
    ['a.b.c.d', 'a[b][c][d]'],
    [0, 0],
]);

test('undotifyKey', function (string|int $expected, string|int $string) {
    expect(Arr::undotifyKey($string))->toBe($expected);
})->with([
    ['foo[bar]', 'foo.bar'],
    ['sources[custom:5bb5537d][condition]', 'sources.custom:5bb5537d.condition'],
    ['foo', 'foo'],
    ['a[b][c][d]', 'a.b.c.d'],
    [0, 0],
]);

test('bracketsToArray', function (array $expected, string $string) {
    expect(Arr::bracketsToArray($string))->toBe($expected);
})->with([
    [['foo', 'bar'], 'foo[bar]'],
    [['sources', 'custom:5bb5537d', 'condition'], 'sources[custom:5bb5537d][condition]'],
    [['foo'], 'foo'],
    [['a', 'b', 'c', 'd'], 'a[b][c][d]'],
]);

test('uniqueDotifiedKeys', function (array $expected, array $array, string $prepend = '') {
    expect(Arr::uniqueDotifiedKeys($array, $prepend))->toBe($expected);
})->with([
    [['title'], ['title' => 'my title'], ''],
    [
        ['title', 'plainText', 'myMatrix', 'myMatrix.et1', 'myMatrix.et1.title', 'myMatrix.et1.text', 'myMatrix.et1.notUsed', 'myMatrix.et2', 'myMatrix.et2.title', 'myMatrix.et2.otherField'],
        [
            'title' => 'my title',
            'plainText' => 'my plain text',
            'myMatrix' => [
                'et1' => [
                    [
                        'title' => 'my title',
                        'text' => 'my text',
                    ],
                    [
                        'title' => 'my title2',
                        'text' => 'my text2',
                        'notUsed' => 'my not used',
                    ],
                ],
                'et2' => [
                    [
                        'title' => 'my title3',
                        'otherField' => 'my other field',
                    ],
                ],
            ],
        ],
        '',
    ],
    [
        ['myPrefix.title', 'myPrefix.plainText', 'myPrefix.myMatrix', 'myPrefix.myMatrix.et1', 'myPrefix.myMatrix.et1.title', 'myPrefix.myMatrix.et1.text', 'myPrefix.myMatrix.et1.notUsed', 'myPrefix.myMatrix.et2', 'myPrefix.myMatrix.et2.title', 'myPrefix.myMatrix.et2.otherField'],
        [
            'title' => 'my title',
            'plainText' => 'my plain text',
            'myMatrix' => [
                'et1' => [
                    [
                        'title' => 'my title',
                        'text' => 'my text',
                    ],
                    [
                        'title' => 'my title2',
                        'text' => 'my text2',
                        'notUsed' => 'my not used',
                    ],
                ],
                'et2' => [
                    [
                        'title' => 'my title3',
                        'otherField' => 'my other field',
                    ],
                ],
            ],
        ],
        'myPrefix',
    ],
]);

// TODO: add tests for: bracketsToArray and uniqueDotifiedKeys
