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
}
