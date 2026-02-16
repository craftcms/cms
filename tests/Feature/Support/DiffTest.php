<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Diff;

test('compare', function (bool $expected, mixed $a, mixed $b, bool $strict) {
    expect(Diff::compare($a, $b, $strict))->toBe($expected);
})->with([
    [true, 1, '1', false],
    [false, 1, '1', true],
    [true, ['foo' => ['bar' => 'baz']], ['foo' => ['bar' => 'baz']], true],
    [false, ['foo' => ['bar' => 'baz']], ['foo' => ['bar' => 'qux']], true],
    [false, ['foo' => true], ['foo' => true, 'bar' => true], true],
]);

test('diff', function (string $expected, mixed $from, mixed $to, int $indent = 2, int $contextLines = 3) {
    expect(Diff::diff($from, $to, $indent, $contextLines))->toEqual($expected);
})->with([
    ['', 'foo', 'foo'],
    ["- foo\n+ bar", 'foo', 'bar'],
    [
        "- - foo\n- - bar\n- - baz\n+ - foo\n+ - bar\n+ - qux",
        ['foo', 'bar', 'baz'],
        ['foo', 'bar', 'qux'],
    ],
    [
        "  foo:\n-   - bar\n-   - baz\n+   - bar\n+   - qux",
        ['foo' => ['bar', 'baz']],
        ['foo' => ['bar', 'qux']],
    ],
    [
        "-     - bar\n-     - baz\n+     - bar\n+     - qux",
        ['foo' => ['bar', 'baz']],
        ['foo' => ['bar', 'qux']],
        4,
        0,
    ],
    [
        rtrim(file_get_contents(dirname(__DIR__, 2).'/_data/diff/expected.diff')),
        include dirname(__DIR__, 2).'/_data/diff/a.php',
        include dirname(__DIR__, 2).'/_data/diff/b.php',
    ],
]);
