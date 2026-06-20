<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\adapter;

use Craft;
use craft\test\TestCase;
use function CraftCms\Yii2Adapter\Helpers\Queries\{buildCondition, convertBindings};

/**
 * Unit tests for the Queries helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class QueriesHelperTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        // avoid worrying about different quote syntaxes
        if (!Craft::$app->getDb()->getIsMysql()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @dataProvider buildConditionDataProvider
     * @param array $expected
     * @param mixed $condition
     */
    public function testBuildCondition(array $expected, mixed $condition): void
    {
        self::assertSame($expected, buildCondition($condition));
    }

    /**
     * @dataProvider convertBindingsDataProvider
     * @param array $expected
     * @param string $sql
     * @param array $params
     */
    public function testConvertBindings(array $expected, string $sql, array $params): void
    {
        self::assertSame($expected, convertBindings($sql, $params));
    }

    /**
     * @return array
     */
    public static function buildConditionDataProvider(): array
    {
        return [
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
        ];
    }

    /**
     * @return array
     */
    public static function convertBindingsDataProvider(): array
    {
        return [
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
        ];
    }
}
