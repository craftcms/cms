<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use craft\elements\db\ElementRelationParamParser;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for ElementRelationParamParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.11
 */
class ElementRelationParamParserTest extends TestCase
{
    /**
     * @dataProvider normalizeRelatedToParamDataProvider
     * @param array $expected
     * @param mixed $param
     */
    public function testNormalizeRelatedToParam(array $expected, mixed $param): void
    {
        self::assertEquals($expected, ElementRelationParamParser::normalizeRelatedToParam($param));
    }

    /**
     * @return array
     */
    public function normalizeRelatedToParamDataProvider(): array
    {
        return [
            [['or'], []],
            [['or'], ['and']],
            [['and', ['element' => ['or', 1], 'field' => null, 'sourceSite' => null], ['element' => ['or', 2], 'field' => null, 'sourceSite' => null]], ['and', 1, 2]],
            [['or', ['element' => ['or', 1, 2, 3], 'field' => null, 'sourceSite' => null]], '1,2,3'],
            [['or', ['element' => ['or', 1], 'field' => null, 'sourceSite' => null]], 1],
            [['or', ['element' => ['or', 1], 'field' => null, 'sourceSite' => null]], ['element' => 1]],
            [['or', ['element' => ['or', 1, 2], 'field' => null, 'sourceSite' => null]], [['element' => 1], 2]],
        ];
    }

    /**
     * @dataProvider normalizeRelatedToCriteriaDataProvider
     * @param array|false $expected
     * @param mixed $param
     */
    public function testNormalizeRelatedToCriteria(array|false $expected, mixed $param): void
    {
        if ($expected === false) {
            self::expectException(InvalidArgumentException::class);
        }

        $param = ElementRelationParamParser::normalizeRelatedToCriteria($param);
        self::assertEquals($expected, $param);
    }

    /**
     * @return array
     */
    public function normalizeRelatedToCriteriaDataProvider(): array
    {
        return [
            [false, ['element' => 1, 'sourceSite' => 'notARealSiteHandle']],
            [['element' => ['or'], 'field' => null, 'sourceSite' => null], []],
            [['element' => ['or', 1], 'field' => null, 'sourceSite' => null], 1],
            [['element' => ['or', 1, 2], 'field' => null, 'sourceSite' => null], [1, 2]],
            [['element' => ['or', 1], 'field' => null, 'sourceSite' => null], ['and', 1]],
            [['element' => ['and', 1, 2], 'field' => null, 'sourceSite' => null], ['and', 1, 2]],
            [['element' => ['or', 1, 2, 3], 'field' => null, 'sourceSite' => null], '1,2,3'],
        ];
    }
}
