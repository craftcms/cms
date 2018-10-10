<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\ElementHelper;

/**
 * Class ElementHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ElementHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider createSlugData
     *
     * @param $result
     * @param $input
     */
    public function testCreateSlug($result, $input)
    {
        $this->assertSame($result, ElementHelper::createSlug($input));
    }

    public function createSlugData()
    {
        $glue = \Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $lower = !\Craft::$app->getConfig()->getGeneral()->allowUppercaseInSlug;

        return [
            ['word_word', 'wordWord'],
            ['word'.$glue.'Word', 'wordWord'],
            ['word'.$glue.'word', 'word word'],
            ['word', 'word'],

        ];
    }

}