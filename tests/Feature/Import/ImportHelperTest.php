<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\ImportHelper;

it('returns an empty array when the field layout is null', function () {
    $result = ImportHelper::getDestinationColsForFieldLayout(null);

    expect($result)->toBe([]);
});

it('marks non-container fields as not a container', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col)->not()->toBeNull();
    expect($col['isContainer'])->toBeFalse();
    expect($col)->not()->toHaveKey('fieldUid');
});

it('marks ImportableElementContainerFieldInterface fields as containers with a fieldUid', function () {
    $entryType = EntryType::factory()->create(['name' => 'Block', 'handle' => 'block']);

    $matrixFieldModel = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$entryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($matrixFieldModel->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myMatrix');

    expect($col)->not()->toBeNull();
    expect($col['isContainer'])->toBeTrue();
    expect($col['fieldUid'])->toBe($matrixFieldModel->uid);
});

it('uses map[attr] as the prefixedHandleForMap for top-level fields without an owner field', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col['prefixedHandleForMap'])->toBe('map[plainText]');
    expect($col['prefixedHandleForMatchCriteria'])->toBe('matchCriteria[plainText]');
    expect($col['prefixedHandle'])->toBe('plainText');
    expect($col['prefixedHandleAsArray'])->toBe(['plainText']);
});

// ensureCleanArray

it('decodes a JSON-encoded string into an array', function () {
    expect(ImportHelper::ensureCleanArray('["a","b"]'))->toBe(['a', 'b']);
});

it('decodes each JSON-encoded string element inside an array', function () {
    expect(ImportHelper::ensureCleanArray(['["a","b"]', '["c","d"]']))->toBe([['a', 'b'], ['c', 'd']]);
});

it('recursively decodes a JSON-encoded string element inside an array', function () {
    expect(ImportHelper::ensureCleanArray(['["a","b"]']))->toBe([['a', 'b']]);
});

// getPrefixedHandlesForMapping – fourth return value

it('returns the handle split into path-part segments as the fourth return value', function () {
    [,,, $parts] = ImportHelper::getPrefixedHandlesForMapping('title', null, null, null, null);

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

    expect($result['blocks'])->toHaveCount(1);
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
