<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Diff;
use craft\test\TestCase;

/**
 * Unit tests for the Diff Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class DiffHelperTest extends TestCase
{
    /**
     * @dataProvider compareDataProvider
     * @param bool $expected
     * @param mixed $a
     * @param mixed $b
     * @param bool $strict
     */
    public function testCompare(bool $expected, mixed $a, mixed $b, bool $strict): void
    {
        self::assertSame($expected, Diff::compare($a, $b, $strict));
    }

    /**
     * @dataProvider diffDataProvider
     * @param string $expected
     * @param mixed $from
     * @param mixed $to
     * @param int $indent
     * @param int $contextLines
     */
    public function testDiff(string $expected, mixed $from, mixed $to, int $indent = 2, int $contextLines = 3): void
    {
        self::assertSame($expected, Diff::diff($from, $to, $indent, $contextLines));
    }

    /**
     * @return array
     */
    public function compareDataProvider(): array
    {
        return [
            [true, 1, '1', false],
            [false, 1, '1', true],
            [true, ['foo' => ['bar' => 'baz']], ['foo' => ['bar' => 'baz']], true],
            [false, ['foo' => ['bar' => 'baz']], ['foo' => ['bar' => 'qux']], true],
            [false, ['foo' => true], ['foo' => true, 'bar' => true], true],
        ];
    }

    /**
     * @return array
     */
    public function diffDataProvider(): array
    {
        return [
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
                rtrim(file_get_contents(dirname(__DIR__, 2) . '/_data/diff/expected.diff')),
                include dirname(__DIR__, 2) . '/_data/diff/a.php',
                include dirname(__DIR__, 2) . '/_data/diff/b.php',
            ],
        ];
    }
}
