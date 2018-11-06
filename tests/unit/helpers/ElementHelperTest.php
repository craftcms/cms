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

        return [
            ['word'.$glue.'Word', 'wordWord'],
            ['word'.$glue.'word', 'word word'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc...dfg', 'abc...dfg'],
            ['abc...dfg', 'abc...(dfg)'],
        ];
    }

    public function testLowerRemoveFromCreateSlug()
    {
        $general =  \Craft::$app->getConfig()->getGeneral();
        $oldAllow = $general->allowUppercaseInSlug;
        $general->allowUppercaseInSlug = false;

        $this->assertSame('word'.$general->slugWordSeparator.'word', ElementHelper::createSlug('word WORD'));

        \Craft::$app->getConfig()->getGeneral()->allowUppercaseInSlug = $oldAllow;
    }

    /**
     * @dataProvider doesuriHaveSlugTagData
     * @param $result
     * @param $input
     */
    public function testDoesUriFormatHaveSlugTag($result, $input)
    {
        $doesIt = ElementHelper::doesUriFormatHaveSlugTag($input);
        $this->assertSame($result, $doesIt);
        $this->assertInternalType('boolean', $doesIt);
    }
    public function doesuriHaveSlugTagData()
    {

        return [
            [true, 'entry/slug'],
            [true, 'entry/{slug}'],
            [false, 'entry/{notASlug}'],
            [false, 'entry/{SLUG}'],
            [false, 'entry/data'],
        ];
    }



}