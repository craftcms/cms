<?php

declare(strict_types=1);

use CraftCms\Cms\Support\ImportHelper;

it('returns an empty array when the field layout is null', function () {
    $result = ImportHelper::getDestinationColsForFieldLayout(null);

    expect($result)->toBe([]);
});

// decodeRecursive

it('decodes a JSON-encoded string into an array', function () {
    expect(ImportHelper::decodeRecursive('["a","b"]'))->toBe(['a', 'b']);
});

it('decodes each JSON-encoded string element inside an array', function () {
    expect(ImportHelper::decodeRecursive(['["a","b"]', '["c","d"]']))->toBe([['a', 'b'], ['c', 'd']]);
});

it('recursively decodes a JSON-encoded string element inside an array', function () {
    expect(ImportHelper::decodeRecursive(['["a","b"]']))->toBe([['a', 'b']]);
});

it('unpacks a JSON-encoded falsy value instead of leaving it as a string', function () {
    expect(ImportHelper::decodeRecursive(['flag' => 'false', 'count' => '0']))->toBe(['flag' => false, 'count' => 0]);
});

// getPrefixedHandlesForMapping – fifth return value

it('returns the handle split into path-part segments as the fifth return value', function () {
    [,,,, $parts] = ImportHelper::getPrefixedHandlesForMapping('title', null, null, null, null);

    expect($parts)->toBe(['title']);
});

// remapData – scalar rules

it('renames a top-level key', function () {
    $result = ImportHelper::remapData(['b' => 'a'], ['a' => 1]);

    expect($result)->toBe(['b' => 1]);
});

it('leaves unmapped keys in the output', function () {
    $result = ImportHelper::remapData(['b' => 'a'], ['a' => 1, 'c' => 2]);

    expect($result)->toBe(['b' => 1, 'c' => 2]);
});

it('doesn’t set a key when the rule is null', function () {
    $result = ImportHelper::remapData(['b' => null], ['a' => 1]);

    expect(array_keys($result))->not()->toContain('b');
    expect($result['a'])->toBe(1);
});

it('sets a key to an empty string when the rule is the sentinel \'""\'', function () {
    $result = ImportHelper::remapData(['b' => '""'], ['a' => 1]);

    expect($result['b'])->toBe('');
});

it('maps a missing source path to null', function () {
    $result = ImportHelper::remapData(['b' => 'missing'], ['a' => 1]);

    expect($result['b'])->toBeNull();
    expect($result['a'])->toBe(1);
});

// remapData – nested objects

it('maps a nested sub-object when leaves share a common path prefix', function () {
    $data = ['address' => ['street' => '123 Main St', 'city' => 'Boston']];
    $map = ['location' => ['street' => 'address.street', 'city' => 'address.city']];

    $result = ImportHelper::remapData($map, $data);

    expect($result['location'])->toBe(['street' => '123 Main St', 'city' => 'Boston']);
    expect($result)->not()->toHaveKey('address');
});

it('keeps unused keys inside a mapped nested object', function () {
    $data = ['address' => ['street' => '123 Main St', 'city' => 'Boston', 'zip' => '02101']];
    $map = ['location' => ['street' => 'address.street', 'city' => 'address.city']];

    $result = ImportHelper::remapData($map, $data);

    expect($result['location']['zip'])->toBe('02101');
});

// remapData – list of rows

it('applies the map to each row when the source resolves to a list', function () {
    $data = ['items' => [['name' => 'Alice', 'age' => 30], ['name' => 'Bob', 'age' => 25]]];
    $map = ['people' => ['fullName' => 'items.name', 'years' => 'items.age']];

    $result = ImportHelper::remapData($map, $data);

    expect($result['people'])->toBe([
        ['fullName' => 'Alice', 'years' => 30],
        ['fullName' => 'Bob', 'years' => 25],
    ]);
});

it('passes non-array rows inside a list through unchanged', function () {
    $data = ['tags' => ['php', 'laravel']];
    $map = ['keywords' => ['upper' => 'tags.upper']];

    $result = ImportHelper::remapData($map, $data);

    expect($result['keywords'])->toBe(['php', 'laravel']);
});

// remapData – block-type containers

it('flattens a block-type container into a flat list with a type key on each row', function () {
    $data = [
        'blocks' => [
            'heading' => [['text' => 'Hello']],
            'text' => [['body' => 'Content']],
        ],
    ];
    $map = [
        'blocks' => [
            'heading' => ['title' => 'blocks.heading.text'],
            'text' => ['content' => 'blocks.text.body'],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['blocks'])->toBe([
        ['type' => 'heading', 'title' => 'Hello'],
        ['type' => 'text', 'content' => 'Content'],
    ]);
});

it('omits items for block types present in source but absent from the map', function () {
    $data = [
        'blocks' => [
            'heading' => [['text' => 'Hello']],
            'image' => [['url' => 'img.png']],
        ],
    ];
    $map = [
        'blocks' => [
            'heading' => ['title' => 'blocks.heading.text'],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['blocks'])->toHaveCount(1);
    expect($result['blocks'][0]['type'])->toBe('heading');
});

it('produces no items for block types in the map that are absent from source', function () {
    $data = [
        'blocks' => [
            'heading' => [['text' => 'Hello']],
        ],
    ];
    $map = [
        'blocks' => [
            'heading' => ['title' => 'blocks.heading.text'],
            'missing' => ['content' => 'blocks.missing.body'],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['blocks'])->toHaveCount(1)
        ->and($result['blocks'][0]['type'])->toBe('heading');
});

it('dispatches each row of a nested inline-typed container to only its matching sibling type', function () {
    $data = [
        'fields' => [
            'plainText' => 'foo3',
            'innerMatrix' => [
                ['type' => 'simple2', 'title' => 'nested 1', 'fields' => ['plainText' => 'bar']],
                ['type' => 'simple', 'title' => 'nested 2', 'fields' => ['plainText' => 'baz'], 'matchCriteria' => ['title' => 'title']],
            ],
        ],
    ];
    $map = [
        'fields' => [
            'plainText' => null,
            'innerMatrix' => [
                'simple' => ['title' => 'fields.innerMatrix.title', 'fields' => ['plainText' => 'fields.innerMatrix.fields.plainText']],
                'simple2' => ['title' => 'fields.innerMatrix.title', 'fields' => ['plainText' => 'fields.innerMatrix.fields.plainText']],
            ],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['fields']['innerMatrix'])->toBe([
        ['type' => 'simple2', 'title' => 'nested 1', 'fields' => ['plainText' => 'bar']],
        ['type' => 'simple', 'title' => 'nested 2', 'fields' => ['plainText' => 'baz'], 'matchCriteria' => ['title' => 'title']],
    ]);
});

it('keeps a nested container field keyed under fields when the block type maps no other field', function () {
    $map = [
        'matrixOuter' => [
            'withMatrix' => [
                'title' => 'matrixOuter.title',
                'fields' => [
                    'matrixInner' => [
                        'withPlainText' => [
                            'title' => 'matrixOuter.fields.matrixInner.title',
                            'fields' => [
                                'plainText' => 'matrixOuter.fields.matrixInner.fields.plainText',
                                'plainText2' => 'matrixOuter.fields.matrixInner.fields.plainText2',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
    $data = [
        'matrixOuter' => [
            [
                'type' => 'withMatrix',
                'matchCriteria' => ['title' => 'title'],
                'title' => 'outer matrix 1',
                'fields' => [
                    'matrixInner' => [
                        [
                            'type' => 'withPlainText',
                            'matchCriteria' => ['title' => 'title'],
                            'title' => 'inner matrix entry',
                            'fields' => ['plainText' => 'foo', 'plainText2' => 'bar'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['matrixOuter'])->toBe([
        [
            'type' => 'withMatrix',
            'title' => 'outer matrix 1',
            'fields' => [
                'matrixInner' => [
                    [
                        'type' => 'withPlainText',
                        'title' => 'inner matrix entry',
                        'fields' => ['plainText' => 'foo', 'plainText2' => 'bar'],
                        'matchCriteria' => ['title' => 'title'],
                    ],
                ],
            ],
            'matchCriteria' => ['title' => 'title'],
        ],
    ]);
});

it('still flattens a grouped-by-type nested container inside a block\'s fields', function () {
    $map = [
        'matrixOuter' => [
            'withMatrix' => [
                'title' => 'matrixOuter.title',
                'fields' => [
                    'matrixInner' => [
                        'withPlainText' => [
                            'title' => 'matrixOuter.fields.matrixInner.withPlainText.title',
                            'fields' => ['plainText' => 'matrixOuter.fields.matrixInner.withPlainText.plainText'],
                        ],
                    ],
                ],
            ],
        ],
    ];
    $data = [
        'matrixOuter' => [
            [
                'type' => 'withMatrix',
                'title' => 'outer matrix 1',
                'fields' => [
                    'matrixInner' => [
                        'withPlainText' => [
                            ['title' => 'inner matrix entry', 'plainText' => 'foo'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['matrixOuter'][0]['fields']['matrixInner'])->toBe([
        [
            'type' => 'withPlainText',
            'title' => 'inner matrix entry',
            'fields' => ['plainText' => 'foo'],
        ],
    ]);
});

// suggestMapValues

it('suggests a source column that exactly matches the destination handle', function () {
    $destinationCols = [['handle' => 'myContent', 'prefixedHandleAsArray' => ['myContent']]];
    $sourceDataCols = [['label' => 'Please select', 'value' => ''], ['label' => 'myContent', 'value' => 'myContent']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe(['myContent' => 'myContent']);
});

it('suggests a source column whose normalized handle matches, even when spelled differently', function () {
    $destinationCols = [['handle' => 'myContent', 'prefixedHandleAsArray' => ['myContent']]];
    $sourceDataCols = [['label' => 'My Content', 'value' => 'My Content']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe(['myContent' => 'My Content']);
});

it('leaves a destination column unmapped when no source column matches', function () {
    $destinationCols = [['handle' => 'myContent', 'prefixedHandleAsArray' => ['myContent']]];
    $sourceDataCols = [['label' => 'unrelated', 'value' => 'unrelated']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([]);
});

it('suggests nothing for a destination the map already has a value for', function () {
    $destinationCols = [['handle' => 'myContent', 'prefixedHandleAsArray' => ['myContent']]];
    $sourceDataCols = [['label' => 'myContent', 'value' => 'myContent']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, ['myContent' => 'alreadyMapped']);

    expect($result)->toBe([]);
});

it('skips container columns since they have no value of their own', function () {
    $destinationCols = [['handle' => 'matrixField', 'prefixedHandleAsArray' => ['matrixField'], 'isContainer' => true]];
    $sourceDataCols = [['label' => 'matrixField', 'value' => 'matrixField']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([]);
});

it('recurses into a MappingColSet\'s subfields', function () {
    $destinationCols = [
        [
            'multiple' => true,
            'subfields' => [
                ['handle' => 'lat', 'prefixedHandleAsArray' => ['location', 'lat']],
                ['handle' => 'lng', 'prefixedHandleAsArray' => ['location', 'lng']],
            ],
        ],
    ];
    $sourceDataCols = [['label' => 'lat', 'value' => 'lat'], ['label' => 'lng', 'value' => 'lng']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe(['location' => ['lat' => 'lat', 'lng' => 'lng']]);
});

it('suggests a nested leaf without repeating a sibling the map already has', function () {
    $destinationCols = [['handle' => 'city', 'prefixedHandleAsArray' => ['address', 'city']]];
    $sourceDataCols = [['label' => 'city', 'value' => 'city']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, ['address' => ['street' => 'streetCol']]);

    expect($result)->toBe(['address' => ['city' => 'city']]);
});

it('prefers the source column that matches the destination\'s whole path over a bare handle match', function () {
    $destinationCols = [
        ['handle' => 'title', 'prefixedHandleAsArray' => ['title']],
        ['handle' => 'title', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'title']],
    ];
    $sourceDataCols = [
        ['label' => 'title', 'value' => 'title'],
        ['label' => 'outerMatrix.withText.title', 'value' => 'outerMatrix.withText.title'],
    ];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    // the more specific match is assigned (and so keyed) first
    expect($result)->toBe([
        'outerMatrix' => ['withText' => ['title' => 'outerMatrix.withText.title']],
        'title' => 'title',
    ]);
});

it('gives a source column that matches a destination\'s whole path to that destination alone', function () {
    $destinationCols = [
        ['handle' => 'title', 'prefixedHandleAsArray' => ['title']],
        ['handle' => 'title', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'title']],
    ];
    $sourceDataCols = [['label' => 'title', 'value' => 'title']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe(['title' => 'title']);
});

it('falls back to a source column that only matches the tail of a nested destination\'s path', function () {
    $destinationCols = [
        ['handle' => 'title', 'prefixedHandleAsArray' => ['title']],
        ['handle' => 'title', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'title']],
    ];
    $sourceDataCols = [['label' => 'withText.title', 'value' => 'withText.title']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([
        'outerMatrix' => ['withText' => ['title' => 'withText.title']],
    ]);
});

it('reuses a partially matched source column across every entry type that wants it', function () {
    $destinationCols = [
        ['handle' => 'title', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'title']],
        ['handle' => 'title', 'prefixedHandleAsArray' => ['outerMatrix', 'withImage', 'title']],
    ];
    $sourceDataCols = [['label' => 'title', 'value' => 'title']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([
        'outerMatrix' => [
            'withText' => ['title' => 'title'],
            'withImage' => ['title' => 'title'],
        ],
    ]);
});

it('matches a source column that names only some of the destination\'s path segments', function () {
    $destinationCols = [
        ['handle' => 'plainText', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'fields', 'plainText']],
    ];
    // no `fields`, no entry type — the segments just have to line up in order
    $sourceDataCols = [['label' => 'outerMatrix.plainText', 'value' => 'outerMatrix.plainText']];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([
        'outerMatrix' => ['withText' => ['fields' => ['plainText' => 'outerMatrix.plainText']]],
    ]);
});

it('does not match a source column whose last segment isn\'t the destination\'s own handle', function () {
    $destinationCols = [
        ['handle' => 'plainText', 'prefixedHandleAsArray' => ['outerMatrix', 'withText', 'fields', 'plainText']],
    ];
    $sourceDataCols = [
        ['label' => 'outerMatrix.fields', 'value' => 'outerMatrix.fields'],
        ['label' => 'outerMatrix.fields.plainText2', 'value' => 'outerMatrix.fields.plainText2'],
    ];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([]);
});

it('never suggests a match criteria column, which belongs to a different tree', function () {
    $destinationCols = [
        ['handle' => 'title', 'prefixedHandleAsArray' => ['matrixOuter', 'withMatrix', 'title']],
    ];
    $sourceDataCols = [[
        'label' => 'matrixOuter.matchCriteria.title',
        'value' => 'matrixOuter.matchCriteria.title',
    ]];

    $result = ImportHelper::suggestMapValues($destinationCols, $sourceDataCols, []);

    expect($result)->toBe([]);
});

// remapData – path resolution

it('resolves a rule path relative to the current base path', function () {
    $data = [
        'rows' => [
            ['orig' => 'Alice'],
            ['orig' => 'Bob'],
        ],
    ];
    $map = ['rows' => ['name' => 'rows.orig']];

    $result = ImportHelper::remapData($map, $data);

    expect($result['rows'][0]['name'])->toBe('Alice');
    expect($result['rows'][1]['name'])->toBe('Bob');
});

it('falls back to root data when the rule path does not start with the current base path', function () {
    $data = [
        'meta' => ['author' => 'Craft'],
        'items' => [['title' => 'Entry 1']],
    ];
    $map = [
        'items' => [
            'title' => 'items.title',
            'source' => 'meta.author',
        ],
    ];

    $result = ImportHelper::remapData($map, $data);

    expect($result['items'][0]['source'])->toBe('Craft');
});
