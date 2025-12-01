<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use craft\db\QueryParam;
use craft\test\TestCase;
use DateTime;

/**
 * Unit tests for the QueryParam class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class QueryParamTest extends TestCase
{
    /**
     * @dataProvider toArrayDataProvider
     *
     * @param array $expected
     * @param mixed $value
     */
    public function testToArray(array $expected, mixed $value): void
    {
        self::assertEquals($expected, QueryParam::toArray($value));
    }

    /**
     * @return array
     */
    public static function toArrayDataProvider(): array
    {
        $date = new DateTime();
        return [
            [[], null],
            [[$date], $date],
            [['foo', 'bar', 'baz'], 'foo,bar,baz'],
            [['foo', 'bar', 'baz'], 'foo, bar, baz'],
            [['foo', 'bar', 'baz'], 'foo,,bar,,baz'],
            [['not', 'foo', 'bar', 'baz'], 'not foo,bar,baz'],
            [['and', 'foo', 'bar', 'baz'], 'and foo, bar, baz'],
            [['or', 'foo', 'bar', 'baz'], 'or foo,,bar,,baz'],
            [['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']],
            [['foo,bar,baz'], 'foo\,bar\,baz'],
        ];
    }

    /**
     * @dataProvider extractOperatorDataProvider
     *
     * @param string|null $expectedOperator
     * @param array $expectedValues
     * @param array $values
     */
    public function testExtractOperator(?string $expectedOperator, array $expectedValues, array $values): void
    {
        $glue = QueryParam::extractOperator($values);
        self::assertEquals($expectedOperator, $glue);
        self::assertEquals($expectedValues, $values);
    }

    /**
     * @return array
     */
    public static function extractOperatorDataProvider(): array
    {
        return [
            ['and', ['foo', 'bar'], ['and', 'foo', 'bar']],
            ['or', ['foo', 'bar'], ['or', 'foo', 'bar']],
            ['not', ['foo', 'bar'], ['not', 'foo', 'bar']],
            ['and', ['foo', 'bar'], ['AND', 'foo', 'bar']],
            [null, ['foo', 'bar'], ['foo', 'bar']],
        ];
    }
}
