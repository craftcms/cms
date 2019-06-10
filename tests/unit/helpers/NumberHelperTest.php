<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Number;
use craft\test\mockclasses\ToString;
use UnitTester;

/**
 * Class NumberHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class NumberHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Properties
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider wordDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testWord($result, $input)
    {
        $word = Number::word($input);
        $this->assertSame($result, $word);
    }

    /**
     * @dataProvider upperAlphaDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testUpperAlpha($result, $input)
    {
        $upperAlpha = Number::upperAlpha($input);
        $this->assertSame($result, $upperAlpha);
    }

    /**
     * @dataProvider lowerAlphaDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testLowerAlpha($result, $input)
    {
        $lowerAlpha = Number::lowerAlpha($input);
        $this->assertSame($result, $lowerAlpha);
    }

    /**
     * @dataProvider upperRomanDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testUpperRoman($result, $input)
    {
        $upperRoman = Number::upperRoman($input);
        $this->assertSame($result, $upperRoman);
    }

    /**
     * @dataProvider lowerRomanDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testLowerRoman($result, $input)
    {
        $lower = Number::lowerRoman($input);
        $this->assertSame($result, $lower);
    }

    /**
     * @dataProvider makeNumericDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testMakeNumeric($result, $input)
    {
        $numeric = Number::makeNumeric($input);
        $this->assertSame($result, $numeric);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function makeNumericDataProvider(): array
    {
        $toStringClass = new ToString('50');

        return [
            [0, false],
            [1, true],
            ['1000', '1000'],
            ['50', $toStringClass],
            [1, 'five'],
            [1, [false]],
            [0, []]
        ];
    }

    /**
     * @return array
     */
    public function lowerRomanDataProvider(): array
    {
        return [
            ['ii', 2],
            ['', 0],
            ['mmmmmmmmmmmmmmmmmmmmmmcxxxii', 22132.22],
            ['', (int)7283231231231231292139.793123123123211237913231],
            ['l', 50],
            ['mi', 1001]
        ];
    }

    /**
     * @return array
     */
    public function upperRomanDataProvider(): array
    {
        return [
            ['II', 2],
            ['', 0],
            ['MMMMMMMMMMMMMMMMMMMMMMCXXXII', 22132.22],
            ['', (int)7283231231231231292139.793123123123211237913231],
            ['L', 50],
            ['MI', 1001]
        ];
    }

    /**
     * @return array
     */
    public function wordDataProvider(): array
    {
        return [
            ['22', 22],
            ['two', 2],
            ['0', 0],
            ['10', 10],
            ['nine', 9]
        ];
    }

    /**
     * @return array
     */
    public function upperAlphaDataProvider(): array
    {
        return [
            ['W', 23],
            ['A', 1],
            ['Z', 26],
            ['', 0],
            ['BC', 55],
            ['FHIM', 111111],
            ['AFSF', 22132.22],
            ['', (int)7283231231231231292139.793123123123211237913231]
        ];
    }

    /**
     * @return array
     */
    public function lowerAlphaDataProvider(): array
    {
        return [
            ['w', 23],
            ['a', 1],
            ['z', 26],
            ['', 0],
            ['bc', 55],
            ['fhim', 111111],
            ['afsf', 22132.22],
            ['', (int)7283231231231231292139.793123123123211237913231]
        ];
    }
}
