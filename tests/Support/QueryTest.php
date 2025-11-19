<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
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
    DB::table(Table::SESSIONS)->delete();

    DB::table(Table::SESSIONS)->insert([
        'userId' => 1,
        'token' => 'test-today',
        'dateCreated' => today(),
        'dateUpdated' => today(),
        'uid' => Str::uuid()->toString(),
    ]);

    DB::table(Table::SESSIONS)->insert([
        'userId' => 1,
        'token' => 'test-yesterday',
        'dateCreated' => now()->subDay()->startOfDay(),
        'dateUpdated' => now()->subDay()->startOfDay(),
        'uid' => Str::uuid()->toString(),
    ]);

    DB::table(Table::SESSIONS)->insert([
        'userId' => 1,
        'token' => 'test-tomorrow',
        'dateCreated' => now()->addDay()->startOfDay(),
        'dateUpdated' => now()->addDay()->startOfDay(),
        'uid' => Str::uuid()->toString(),
    ]);

    $query = DB::table(Table::SESSIONS)->whereDateParam('dateCreated', $param);

    expect(
        $query
            ->pluck('token')
            // Trim because on pgsql these are fixed length
            ->map(fn ($token) => trim((string) $token))
            ->all()
    )->toEqual($expected);
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
