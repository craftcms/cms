<?php

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\DB;

test('whereParam', function (string $column, mixed $param, array $expected) {
    DB::table(Table::MIGRATIONS)->delete();

    foreach (range(1, 5) as $i) {
        DB::table(Table::MIGRATIONS)->insert([
            'track' => 'test',
            'migration' => 'test-'.$i,
            'batch' => $i,
        ]);
    }

    $query = DB::table(Table::MIGRATIONS);

    expect($query->whereParam($column, $param)->pluck('batch')->all())->toEqual($expected);
})->with([
    ['batch', '1', [1]],
    ['batch', '1, 2', [1, 2]],
    ['batch', [1, 2], [1, 2]],
    ['batch', 'and >1, <3', [2]],
    ['batch', '>1, <3', [1, 2, 3, 4, 5]], // OR
    ['batch', '<= 1', [1]],
    ['batch', '!= 1', [2, 3, 4, 5]],
    ['batch', 'and not 1, not 3', [2, 4, 5]],
    ['batch', ':empty:', []],
    ['batch', ':notempty:', [1, 2, 3, 4, 5]],
    ['batch', ':notempty:', [1, 2, 3, 4, 5]],
    ['migration', 'test*', [1, 2, 3, 4, 5]],
    ['migration', 'test-1', [1]],
]);

test('whereNumericParam throws if not numeric', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid numeric value: foo');

    DB::table(Table::MIGRATIONS)->whereNumericParam('batch', 'foo');
});

test('whereBooleanParam', function () {
    DB::table(Table::MIGRATIONS)->insert([
        'track' => 'false',
        'migration' => 'false',
        'batch' => 0,
    ]);

    DB::table(Table::MIGRATIONS)->insert([
        'track' => 'true',
        'migration' => 'true',
        'batch' => 1,
    ]);

    // @TODO: 'false' is considered a true value, should we change this?
    foreach ([true, 1, '1', 'true', 'false'] as $param) {
        expect(DB::table(Table::MIGRATIONS)->whereBooleanParam('batch', $param)->pluck('track'))->toContain('true');
        expect(DB::table(Table::MIGRATIONS)->whereBooleanParam('batch', $param)->pluck('track'))->not()->toContain('false');
    }

    foreach ([false, 0] as $param) {
        expect(DB::table(Table::MIGRATIONS)->whereBooleanParam('batch', $param)->pluck('track'))->not()->toContain('true');
        expect(DB::table(Table::MIGRATIONS)->whereBooleanParam('batch', $param)->pluck('track'))->toContain('false');
    }
});

test('whereDateParam', function (string $param, array $expected) {
    Volume::factory()->create([
        'name' => 'test-today',
        'dateCreated' => today(),
    ]);

    Volume::factory()->create([
        'name' => 'test-yesterday',
        'dateCreated' => now()->subDay()->startOfDay(),
    ]);

    Volume::factory()->create([
        'name' => 'test-tomorrow',
        'dateCreated' => now()->addDay()->startOfDay(),
    ]);

    $query = DB::table(Table::VOLUMES)->whereDateParam('dateCreated', $param);

    expect($query->pluck('name')->all())->toEqual($expected);
})->with([
    ['today', ['test-today']],
    ['tomorrow', ['test-tomorrow']],
    ['> today', ['test-tomorrow']],
    ['>= today', ['test-today', 'test-tomorrow']],
    ['< today, > today', ['test-yesterday', 'test-tomorrow']],
]);

test('escapeParam', function (string $param, string $expected) {
    expect(Query::escapeParam($param))->toBe($expected);
})->with([
    ['*', '\*'],
    [',', '\,'],
    [',*', '\,\*'],
    ['\,\*', '\,\*'],
    ['>10', '\>10'],
    ['not :empty:', '\not :empty:'],
    [':notempty:', '\:notempty:'],
    [':empty:', '\:empty:'],
    ['NOT :EMPTY:', '\NOT :EMPTY:'],
    [':NOTEMPTY:', '\:NOTEMPTY:'],
    [':EMPTY:', '\:EMPTY:'],
    [':foo:', ':foo:'],
]);

test('escapeCommas', function (string $param, string $expected) {
    expect(Query::escapeCommas($param))->toBe($expected);
})->with([
    ['foo, bar', 'foo\, bar'],
    ['foo, bar*', 'foo\, bar*'],
    ['foo\, bar', 'foo\, bar'],
]);

test('escapeForLike', function (string $param, string $expected) {
    expect(Query::escapeForLike($param))->toBe($expected);
})->with([
    ['_foo', '\\_foo'],
    ['foo_bar', 'foo\\_bar'],
    ['foo_', 'foo\\_'],
]);

test('parseColumnType', function (string $columnType, ?string $expected) {
    expect(Query::parseColumnType($columnType))->toBe($expected);
})->with([
    ['STRING(255)', 'string'],
    ['DECIMAL(14,4)', 'decimal'],
    ['"invalid"', null],
]);

test('parseColumnLength', function (string $columnType, ?int $expected) {
    expect(Query::parseColumnLength($columnType))->toBe($expected);
})->with([
    'string with length' => ['STRING(255)', 255],
    'integer with length' => ['INTEGER(11)', 11],
    'no length' => ['TEXT', null],
    'decimal with precision and scale returns null' => ['DECIMAL(14,4)', null],
    'invalid' => ['"invalid"', null],
]);

test('parseColumnPrecisionAndScale', function (string $columnType, ?array $expected) {
    expect(Query::parseColumnPrecisionAndScale($columnType))->toBe($expected);
})->with([
    'decimal' => ['DECIMAL(14,4)', [14, 4]],
    'decimal with spaces' => ['DECIMAL(10, 2)', [10, 2]],
    'no precision/scale' => ['INTEGER(11)', null],
    'no parens' => ['TEXT', null],
    'invalid' => ['"invalid"', null],
]);

test('getSimplifiedColumnType', function (string $columnType, string $expected) {
    expect(Query::getSimplifiedColumnType($columnType))->toBe($expected);
})->with([
    'integer is numeric' => ['INTEGER', Query::SIMPLE_TYPE_NUMERIC],
    'bigint is numeric' => ['BIGINT', Query::SIMPLE_TYPE_NUMERIC],
    'decimal is numeric' => ['DECIMAL(14,4)', Query::SIMPLE_TYPE_NUMERIC],
    'float is numeric' => ['FLOAT', Query::SIMPLE_TYPE_NUMERIC],
    'tinyint is numeric' => ['TINYINT', Query::SIMPLE_TYPE_NUMERIC],
    'string is textual' => ['STRING(255)', Query::SIMPLE_TYPE_TEXTUAL],
    'text is textual' => ['TEXT', Query::SIMPLE_TYPE_TEXTUAL],
    'char is textual' => ['CHAR(1)', Query::SIMPLE_TYPE_TEXTUAL],
    'enum is textual' => ['ENUM', Query::SIMPLE_TYPE_TEXTUAL],
    'boolean stays boolean' => ['BOOLEAN', Query::TYPE_BOOLEAN],
    'datetime stays datetime' => ['DATETIME', Query::TYPE_DATETIME],
    'json stays json' => ['JSON', Query::TYPE_JSON],
]);

test('areColumnTypesCompatible', function (string $typeA, string $typeB, bool $expected) {
    expect(Query::areColumnTypesCompatible($typeA, $typeB))->toBe($expected);
})->with([
    'same type' => ['INTEGER', 'INTEGER', true],
    'both numeric' => ['INTEGER', 'BIGINT', true],
    'both textual' => ['STRING(255)', 'TEXT', true],
    'numeric and textual' => ['INTEGER', 'STRING(255)', false],
    'numeric and boolean' => ['INTEGER', 'BOOLEAN', false],
    'decimal and float' => ['DECIMAL(14,4)', 'FLOAT', true],
]);

test('isNumericColumnType', function (string $columnType, bool $expected) {
    expect(Query::isNumericColumnType($columnType))->toBe($expected);
})->with([
    'integer' => ['INTEGER', true],
    'bigint' => ['BIGINT', true],
    'float' => ['FLOAT', true],
    'decimal' => ['DECIMAL(14,4)', true],
    'string' => ['STRING(255)', false],
    'text' => ['TEXT', false],
    'boolean' => ['BOOLEAN', false],
]);

test('isTextualColumnType', function (string $columnType, bool $expected) {
    expect(Query::isTextualColumnType($columnType))->toBe($expected);
})->with([
    'string' => ['STRING(255)', true],
    'text' => ['TEXT', true],
    'char' => ['CHAR(1)', true],
    'tinytext' => ['TINYTEXT', true],
    'mediumtext' => ['MEDIUMTEXT', true],
    'longtext' => ['LONGTEXT', true],
    'enum' => ['ENUM', true],
    'integer' => ['INTEGER', false],
    'boolean' => ['BOOLEAN', false],
]);

test('unescapeParam', function (string $param, string $expected) {
    expect(Query::unescapeParam($param))->toBe($expected);
})->with([
    'escaped asterisk' => ['\*', '*'],
    'escaped comma' => ['\,', ','],
    'escaped comma and asterisk' => ['\,\*', ',*'],
    'escaped operator >' => ['\>10', '>10'],
    'escaped not' => ['\not :empty:', 'not :empty:'],
    'no escaping needed' => ['hello', 'hello'],
]);

test('escapeParam and unescapeParam are inverse operations', function (string $value) {
    expect(Query::unescapeParam(Query::escapeParam($value)))->toBe($value);
})->with([
    'asterisk' => ['*'],
    'comma' => [','],
    'operator' => ['>10'],
    'not empty' => ['not :empty:'],
    ':notempty:' => [':notempty:'],
    ':empty:' => [':empty:'],
    'plain text' => ['hello world'],
]);

test('normalizeParam resolves values', function () {
    $value = [1, 2, 3];

    $result = Query::normalizeParam($value, fn ($item) => $item * 10);

    expect($result)->toBeTrue();
    expect($value)->toBe([10, 20, 30]);
});

test('normalizeParam preserves operator prefix', function () {
    $value = ['and', 1, 2];

    $result = Query::normalizeParam($value, fn ($item) => $item * 10);

    expect($result)->toBeTrue();
    expect($value)->toBe(['and', 10, 20]);
});

test('normalizeParam returns false when resolver returns falsy', function () {
    $value = [1, 2, 3];

    $result = Query::normalizeParam($value, fn ($item) => $item === 2 ? null : $item);

    expect($result)->toBeFalse();
});

test('normalizeParam handles null value', function () {
    $value = null;

    $result = Query::normalizeParam($value, fn ($item) => $item);

    expect($result)->toBeTrue();
});

test('normalizeParam wraps non-array value', function () {
    $value = 5;

    $result = Query::normalizeParam($value, fn ($item) => $item * 10);

    expect($result)->toBeTrue();
    expect($value)->toBe([50]);
});

test('whereMoneyParam', function () {
    DB::table(Table::MIGRATIONS)->delete();

    DB::table(Table::MIGRATIONS)->insert([
        'track' => 'money-100',
        'migration' => 'test-money-1',
        'batch' => 100,
    ]);

    DB::table(Table::MIGRATIONS)->insert([
        'track' => 'money-200',
        'migration' => 'test-money-2',
        'batch' => 200,
    ]);

    DB::table(Table::MIGRATIONS)->insert([
        'track' => 'money-300',
        'migration' => 'test-money-3',
        'batch' => 300,
    ]);

    // 1.00 USD = 100 minor units
    $query = DB::table(Table::MIGRATIONS)
        ->where('migration', 'like', 'test-money-%')
        ->whereMoneyParam('batch', 'USD', '1.00');

    expect($query->pluck('track')->all())->toBe(['money-100']);

    // > 1.00 USD
    $query = DB::table(Table::MIGRATIONS)
        ->where('migration', 'like', 'test-money-%')
        ->whereMoneyParam('batch', 'USD', '> 1.00');

    expect($query->pluck('track')->all())->toBe(['money-200', 'money-300']);
});

test('prepareDateForDb with DateTime', function () {
    $date = new \DateTime('2024-06-15 14:30:00', new \DateTimeZone('America/New_York'));

    $result = Query::prepareDateForDb($date);

    // Should be converted to UTC
    expect($result)->toBe('2024-06-15 18:30:00');
});

test('prepareDateForDb with null returns null', function () {
    expect(Query::prepareDateForDb(null))->toBeNull();
});

test('prepareDateForDb with invalid value returns null', function () {
    expect(Query::prepareDateForDb('not-a-date'))->toBeNull();
});
