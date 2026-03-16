<?php

declare(strict_types=1);

use CraftCms\Cms\Support\MemoizableArray;

function items(): array
{
    return [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin', 'active' => true],
        ['id' => 2, 'name' => 'Bob', 'role' => 'editor', 'active' => true],
        ['id' => 3, 'name' => 'Charlie', 'role' => 'editor', 'active' => false],
        ['id' => 4, 'name' => 'Diana', 'role' => 'admin', 'active' => false],
    ];
}

function objects(): array
{
    return array_map(fn (array $item) => (object) $item, items());
}

describe('construction & basic interface', function () {
    test('all returns the original elements', function () {
        $array = new MemoizableArray(items());

        expect($array->all())->toBe(items());
    });

    test('count returns the number of elements', function () {
        $array = new MemoizableArray(items());

        expect($array)->toHaveCount(4);
        expect(count($array))->toBe(4);
    });

    test('count returns zero for empty array', function () {
        $array = new MemoizableArray([]);

        expect($array)->toHaveCount(0);
    });

    test('it is iterable', function () {
        $array = new MemoizableArray(items());
        $collected = [];

        foreach ($array as $item) {
            $collected[] = $item;
        }

        expect($collected)->toBe(items());
    });

    test('getIterator returns an ArrayIterator', function () {
        $array = new MemoizableArray(items());

        expect($array->getIterator())->toBeInstanceOf(ArrayIterator::class);
    });
});

describe('where', function () {
    test('where filters by key and value', function () {
        $array = new MemoizableArray(items());

        $admins = $array->where('role', 'admin');

        expect($admins)->toBeInstanceOf(MemoizableArray::class);
        expect($admins->all())->toHaveCount(2);
        expect(array_column($admins->all(), 'name'))->toBe(['Alice', 'Diana']);
    });

    test('where with default value filters for truthy', function () {
        $array = new MemoizableArray(items());

        $active = $array->where('active');

        expect($active->all())->toHaveCount(2);
        expect(array_column($active->all(), 'name'))->toBe(['Alice', 'Bob']);
    });

    test('where with strict comparison', function () {
        $elements = [
            ['id' => 1, 'status' => '1'],
            ['id' => 2, 'status' => 1],
            ['id' => 3, 'status' => true],
        ];
        $array = new MemoizableArray($elements);

        $loose = $array->where('status', 1, strict: false);
        $strict = $array->where('status', 1, strict: true);

        expect($loose->all())->toHaveCount(3);
        expect($strict->all())->toHaveCount(1);
        expect(array_first($strict->all())['id'])->toBe(2);
    });

    test('where returns empty when no match', function () {
        $array = new MemoizableArray(items());

        $result = $array->where('role', 'superadmin');

        expect($result->all())->toBe([]);
        expect($result)->toHaveCount(0);
    });

    test('where on object properties', function () {
        $array = new MemoizableArray(objects());

        $editors = $array->where('role', 'editor');

        expect($editors->all())->toHaveCount(2);
        expect(array_first($editors->all())->name)->toBe('Bob');
        expect(array_values($editors->all())[1]->name)->toBe('Charlie');
    });
});

describe('whereIn', function () {
    test('whereIn filters by multiple values', function () {
        $array = new MemoizableArray(items());

        $result = $array->whereIn('id', [1, 3]);

        expect($result)->toBeInstanceOf(MemoizableArray::class);
        expect($result->all())->toHaveCount(2);
        expect(array_column($result->all(), 'name'))->toBe(['Alice', 'Charlie']);
    });

    test('whereIn with strict comparison', function () {
        $elements = [
            ['id' => '1', 'name' => 'string-one'],
            ['id' => 1, 'name' => 'int-one'],
            ['id' => 2, 'name' => 'int-two'],
        ];
        $array = new MemoizableArray($elements);

        $loose = $array->whereIn('id', [1], strict: false);
        $strict = $array->whereIn('id', [1], strict: true);

        expect($loose->all())->toHaveCount(2);
        expect($strict->all())->toHaveCount(1);
        expect(array_first($strict->all())['name'])->toBe('int-one');
    });

    test('whereIn returns empty when no values match', function () {
        $array = new MemoizableArray(items());

        $result = $array->whereIn('id', [99, 100]);

        expect($result->all())->toBe([]);
    });

    test('whereIn on object properties', function () {
        $array = new MemoizableArray(objects());

        $result = $array->whereIn('name', ['Alice', 'Charlie']);

        expect($result->all())->toHaveCount(2);
        expect(array_first($result->all())->id)->toBe(1);
        expect(array_values($result->all())[1]->id)->toBe(3);
    });
});

describe('firstWhere', function () {
    test('firstWhere returns the first matching element', function () {
        $array = new MemoizableArray(items());

        $editor = $array->firstWhere('role', 'editor');

        expect($editor)->toBe(['id' => 2, 'name' => 'Bob', 'role' => 'editor', 'active' => true]);
    });

    test('firstWhere returns null when no match', function () {
        $array = new MemoizableArray(items());

        $result = $array->firstWhere('role', 'superadmin');

        expect($result)->toBeNull();
    });

    test('firstWhere with default value filters for truthy', function () {
        $array = new MemoizableArray(items());

        $result = $array->firstWhere('active');

        expect($result['name'])->toBe('Alice');
    });

    test('firstWhere with strict comparison', function () {
        $elements = [
            ['id' => '1', 'name' => 'string-one'],
            ['id' => 1, 'name' => 'int-one'],
        ];
        $array = new MemoizableArray($elements);

        $loose = $array->firstWhere('id', 1, strict: false);
        $strict = $array->firstWhere('id', 1, strict: true);

        expect($loose['name'])->toBe('string-one');
        expect($strict['name'])->toBe('int-one');
    });

    test('firstWhere on object properties', function () {
        $array = new MemoizableArray(objects());

        $result = $array->firstWhere('id', 3);

        expect($result->name)->toBe('Charlie');
    });
});

describe('memoization', function () {
    test('where returns the same instance for identical queries', function () {
        $array = new MemoizableArray(items());

        $first = $array->where('role', 'admin');
        $second = $array->where('role', 'admin');

        expect($first)->toBe($second);
    });

    test('whereIn returns the same instance for identical queries', function () {
        $array = new MemoizableArray(items());

        $first = $array->whereIn('id', [1, 2]);
        $second = $array->whereIn('id', [1, 2]);

        expect($first)->toBe($second);
    });

    test('firstWhere returns the same result for identical queries', function () {
        $array = new MemoizableArray(items());

        $first = $array->firstWhere('id', 1);
        $second = $array->firstWhere('id', 1);

        expect($first)->toBe($second);
    });

    test('firstWhere memoizes null results', function () {
        $callCount = 0;
        $array = new MemoizableArray(
            [['id' => 1]],
            function ($element) use (&$callCount) {
                $callCount++;

                return $element;
            },
        );

        $first = $array->firstWhere('id', 999);
        $second = $array->firstWhere('id', 999);

        expect($first)->toBeNull();
        expect($second)->toBeNull();
        expect($callCount)->toBe(0);
    });

    test('different queries produce different memoized results', function () {
        $array = new MemoizableArray(items());

        $admins = $array->where('role', 'admin');
        $editors = $array->where('role', 'editor');

        expect($admins)->not->toBe($editors);
        expect($admins->all())->toHaveCount(2);
        expect($editors->all())->toHaveCount(2);
    });

    test('strict and non-strict queries are memoized separately', function () {
        $elements = [
            ['id' => '1', 'name' => 'string'],
            ['id' => 1, 'name' => 'int'],
        ];
        $array = new MemoizableArray($elements);

        $loose = $array->where('id', 1, strict: false);
        $strict = $array->where('id', 1, strict: true);

        expect($loose)->not->toBe($strict);
        expect($loose->all())->toHaveCount(2);
        expect($strict->all())->toHaveCount(1);
    });
});

describe('normalizer', function () {
    test('normalizer is applied when calling all', function () {
        $array = new MemoizableArray(
            [['name' => 'alice'], ['name' => 'bob']],
            fn (array $element) => array_merge($element, ['normalized' => true]),
        );

        $result = $array->all();

        expect($result)->toHaveCount(2);
        expect($result[0])->toBe(['name' => 'alice', 'normalized' => true]);
        expect($result[1])->toBe(['name' => 'bob', 'normalized' => true]);
    });

    test('normalizer is applied when iterating', function () {
        $array = new MemoizableArray(
            [['name' => 'alice']],
            fn (array $element) => array_merge($element, ['normalized' => true]),
        );

        $collected = [];

        foreach ($array as $item) {
            $collected[] = $item;
        }

        expect($collected[0])->toBe(['name' => 'alice', 'normalized' => true]);
    });

    test('normalizer is applied to firstWhere results', function () {
        $array = new MemoizableArray(
            [['id' => 1, 'name' => 'alice'], ['id' => 2, 'name' => 'bob']],
            fn (array $element) => array_merge($element, ['normalized' => true]),
        );

        $result = $array->firstWhere('id', 1);

        expect($result)->toBe(['id' => 1, 'name' => 'alice', 'normalized' => true]);
    });

    test('normalizer is called only once per element', function () {
        $callCounts = [];

        $array = new MemoizableArray(
            [['id' => 1], ['id' => 2], ['id' => 3]],
            function (array $element, int $key) use (&$callCounts) {
                $callCounts[$key] = ($callCounts[$key] ?? 0) + 1;

                return $element;
            },
        );

        $array->all();
        $array->all();

        expect($callCounts)->toBe([0 => 1, 1 => 1, 2 => 1]);
    });

    test('normalizer receives the element and key', function () {
        $receivedArgs = [];

        $array = new MemoizableArray(
            [['id' => 1]],
            function (mixed $element, int|string $key) use (&$receivedArgs) {
                $receivedArgs = ['element' => $element, 'key' => $key];

                return $element;
            },
        );

        $array->all();

        expect($receivedArgs['element'])->toBe(['id' => 1]);
        expect($receivedArgs['key'])->toBe(0);
    });

    test('normalizer carries through to where results', function () {
        $normalizeCount = 0;

        $array = new MemoizableArray(
            [
                ['id' => 1, 'role' => 'admin'],
                ['id' => 2, 'role' => 'editor'],
                ['id' => 3, 'role' => 'admin'],
            ],
            function (array $element) use (&$normalizeCount) {
                $normalizeCount++;

                return array_merge($element, ['normalized' => true]);
            },
        );

        $admins = $array->where('role', 'admin');
        $result = $admins->all();

        expect($result)->toHaveCount(2);
        expect($result[0]['normalized'])->toBeTrue();
        expect($result[1]['normalized'])->toBeTrue();
    });

    test('without normalizer all returns elements as-is', function () {
        $original = items();
        $array = new MemoizableArray($original);

        expect($array->all())->toBe($original);
    });
});

describe('chaining', function () {
    test('where results can be further filtered with where', function () {
        $array = new MemoizableArray(items());

        $activeAdmins = $array->where('role', 'admin')->where('active', true);

        expect($activeAdmins->all())->toHaveCount(1);
        expect($activeAdmins->all()[0]['name'])->toBe('Alice');
    });

    test('where results can be queried with firstWhere', function () {
        $array = new MemoizableArray(items());

        $result = $array->where('role', 'editor')->firstWhere('active', false);

        expect($result['name'])->toBe('Charlie');
    });

    test('whereIn results can be further filtered', function () {
        $array = new MemoizableArray(items());

        $result = $array->whereIn('id', [1, 2, 3])->where('active', false);

        expect($result->all())->toHaveCount(1);
        expect(array_first($result->all())['name'])->toBe('Charlie');
    });
});

describe('edge cases', function () {
    test('empty array works with all operations', function () {
        $array = new MemoizableArray([]);

        expect($array->all())->toBe([]);
        expect($array->where('id', 1)->all())->toBe([]);
        expect($array->whereIn('id', [1, 2])->all())->toBe([]);
        expect($array->firstWhere('id', 1))->toBeNull();
        expect($array)->toHaveCount(0);
    });

    test('count reflects original elements not filtered results', function () {
        $array = new MemoizableArray(items());

        $filtered = $array->where('role', 'admin');

        expect($array)->toHaveCount(4);
        expect($filtered)->toHaveCount(2);
    });
});
