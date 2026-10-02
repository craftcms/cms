<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use function CraftCms\Yii2Adapter\Helpers\Queries\buildCondition;
use function CraftCms\Yii2Adapter\Helpers\Queries\convertBindings;

uses(TestCase::class);

beforeEach(function() {
    // avoid worrying about different quote syntaxes
    if (!DB::isMysql()) {
        $this->markTestSkipped();
    }
});

test('buildCondition', function(array $expected, mixed $condition) {
    expect(buildCondition($condition))->toBe($expected);
})->with([
    'and' => [
        ['(`foo`=?) AND (`bar`=?)', ['a', 'b']],
        ['foo' => 'a', 'bar' => 'b'],
    ],
    'and-explicit' => [
        ['(`foo`=?) AND (`bar`=?)', ['a', 'b']],
        ['and', ['foo' => 'a'], ['bar' => 'b']],
    ],
    'or' => [
        ['(`foo`=?) OR (`bar`=?)', ['a', 'b']],
        ['or', ['foo' => 'a'], ['bar' => 'b']],
    ],
    'in' => [
        ['`foo` IN (?, ?, ?)', ['a', 'b', 'c']],
        ['in', 'foo', ['a', 'b', 'c']],
    ],
    'empty' => [
        ['', null],
        [],
    ],
]);

test('convertBindings', function(array $expected, string $sql, array $params) {
    expect(convertBindings($sql, $params))->toBe($expected);
})->with([
    'and' => [
        ['`foo`=? AND `bar`=?', ['a', 'b']],
        '`foo`=:foo AND `bar`=:bar',
        [
            'foo' => 'a',
            'bar' => 'b',
        ],
    ],
    'backward-and' => [
        ['`foo`=? AND `bar`=?', ['a', 'b']],
        '`foo`=:foo AND `bar`=:bar',
        [
            'bar' => 'b',
            'foo' => 'a',
        ],
    ],
    'multiple' => [
        ['`foo`=? AND `bar`=?', ['a', 'a']],
        '`foo`=:foo AND `bar`=:foo',
        [
            'foo' => 'a',
        ],
    ],
]);
