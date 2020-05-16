<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Stringy;
use UnitTester;

/**
 * Unit tests for the Stringy helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.18
 */
class StringyHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider langSpecificCharsArrayDataProvider
     * @param string $language
     * @param string $orig
     * @param string $replace
     */
    public function testLangSpecificCharsArray(string $language, string $orig, string $replace)
    {
        $array = Stringy::getLangSpecificCharsArray($language);
        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $index = array_search($orig, $array[0]);
        $this->assertIsInt($index);
        $this->assertSame($replace, $array[1][$index]);
    }

    /**
     * @return array
     */
    public function langSpecificCharsArrayDataProvider(): array
    {
        return [
            ['de', 'ä', 'ae'],
            ['de-DE', 'ä', 'ae'],
        ];
    }

    /**
     *
     */
    public function testAsciiCharMap()
    {
        $map = (new Stringy())->getAsciiCharMap();
        $this->assertIsArray($map);
        $this->assertIsArray($map['a']);
        $this->assertIsString($map['a'][0]);
    }
}
