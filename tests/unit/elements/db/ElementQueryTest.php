<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use craft\elements\Entry;
use craft\test\TestCase;
use yii\base\NotSupportedException;

/**
 * Unit tests for ElementQuery
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.11
 */
class ElementQueryTest extends TestCase
{
    /**
     * @dataProvider relatedToDataProvider
     * @param array|false $expected
     * @param mixed $relatedToParam
     * @param mixed $andRelatedToParam
     */
    public function testAndRelatedTo(array|false $expected, mixed $relatedToParam, mixed $andRelatedToParam): void
    {
        if ($expected === false) {
            self::expectException(NotSupportedException::class);
        }

        $query = Entry::find()
            ->relatedTo($relatedToParam)
            ->andRelatedTo($andRelatedToParam);

        self::assertEquals($expected, $query->relatedTo);
    }

    /**
     * @dataProvider normalizeOrderByDataProvider
     * @param array $expected
     * @param mixed $columns
     * @return void
     */
    public function testNormalizeOrderBy(array $expected, mixed $columns): void
    {
        $query = Entry::find();
        self::assertSame($expected, $this->invokeMethod($query, 'normalizeOrderBy', [$columns]));
    }

    /**
     * @return array
     */
    public function relatedToDataProvider(): array
    {
        return [
            [false, ['or', ['targetElement' => 1], ['targetElement' => 2]], 3],
            [['or', 1, 2], null, ['or', 1, 2]],
            [['and', ['field' => null, 'sourceSite' => null, 'element' => ['or', 1]], ['field' => null, 'sourceSite' => null, 'element' => ['or', 1, 2]]], ['or', 1], [1, 2]],
        ];
    }

    /**
     * @return array
     */
    public function normalizeOrderByDataProvider(): array
    {
        return [
            [['score' => SORT_DESC], 'score'],
            [['score' => SORT_DESC], ' score '],
            [['score' => SORT_ASC], 'score asc'],
            [['score' => SORT_ASC], ' score asc '],
            [['foo' => SORT_ASC, 'score' => SORT_DESC, 'bar' => SORT_ASC], 'foo, score, bar'],
            [['foo' => SORT_DESC, 'score' => SORT_ASC, 'bar' => SORT_DESC], 'foo desc, score asc, bar desc'],
        ];
    }
}
