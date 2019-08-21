<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\StringHelper;
use craft\test\mockclasses\ToString;
use stdClass;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use function mb_strlen;
use function mb_strpos;

/**
 * Unit tests for the String Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class StringHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    public function testUtf8Definition()
    {
        $this->assertSame('UTF-8', StringHelper::UTF8);
    }

    /**
     * @dataProvider afterFirstDataProvider
     * @param $expected
     * @param $string
     * @param $separator
     * @param $caseSensitive
     */
    public function testAfterFirst($expected, $string, $separator, $caseSensitive)
    {
        $actual = StringHelper::afterFirst($string, $separator, $caseSensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider afterLastDataProvider
     * @param $expected
     * @param $string
     * @param $separator
     * @param $caseSensitive
     */
    public function testAfterLast($expected, $string, $separator, $caseSensitive)
    {
        $actual = StringHelper::afterLast($string, $separator, $caseSensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider appendDataProvider
     * @param $expected
     * @param $string
     * @param $append
     */
    public function testAppend($expected, $string, $append)
    {
        $actual = StringHelper::append($string, $append);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testAppendRandomString()
    {
        $testArray = [
            'abc'       => [1, 1],
            'öäü'       => [10, 10],
            ''          => [10, 0],
            ' '         => [10, 10],
            'κόσμε-öäü' => [10, 10],
        ];

        foreach ($testArray as $testString => $testResult) {
            $actual = StringHelper::appendRandomString('', $testResult[0], $testString);
            $this->assertSame($testResult[1], StringHelper::length($actual));
        }
    }

    /**
     *
     */
    public function testAppendUniqueIdentifier()
    {
        $uniqueIds = [];
        for ($i = 0; $i <= 100; ++$i) {
            $uniqueIds[] = StringHelper::appendUniqueIdentifier('');
        }

        // detect duplicate values in the array
        foreach (array_count_values($uniqueIds) as $uniqueId => $count) {
            $this->assertSame(1, $count);
        }

        // check the string length
        foreach ($uniqueIds as $uniqueId) {
            static::assertSame(32, strlen($uniqueId));
        }
    }

    /**
     * @dataProvider atDataProvider
     * @param $expected
     * @param $string
     * @param $position
     */
    public function testAt($expected, $string, $position)
    {
        $actual = StringHelper::at($string, $position);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testBeforeFirst()
    {
        $testArray = [
            ['', '', 'b', true],
            ['<h1>test</h1>', '', 'b', true],
            ['foo<h1></h1>bar', 'foo<h1></h1>', 'b', true],
            ['<h1></h1> ', '', 'b', true],
            ['</b></b>', '</', 'b', true],
            ['öäü<strong>lall</strong>', '', 'b', true],
            [' b<b></b>', ' ', 'b', true],
            ['<b><b>lall</b>', '<', 'b', true],
            ['</b>lall</b>', '</', 'b', true],
            ['[b][/b]', '[', 'b', true],
            ['[B][/B]', '', 'b', true],
            ['κόσμbε ¡-öäü', 'κόσμ', 'b', true],
            ['', '', 'b', false],
            ['<h1>test</h1>', '', 'b', false],
            ['foo<h1></h1>Bar', 'foo<h1></h1>', 'b', false],
            ['foo<h1></h1>bar', 'foo<h1></h1>', 'b', false],
            ['<h1></h1> ', '', 'b', false],
            ['</b></b>', '</', 'b', false],
            ['öäü<strong>lall</strong>', '', 'b', false],
            [' b<b></b>', ' ', 'b', false],
            ['<b><b>lall</b>', '<', 'b', false],
            ['</b>lall</b>', '</', 'b', false],
            ['[B][/B]', '[', 'b', false],
            ['κόσμbε ¡-öäü', 'κόσμ', 'b', false],
            ['Bκόσμbε', '', 'b', false],
        ];

        foreach ($testArray as $testResult) {
            if ($testResult[3]) {
                $actual = StringHelper::beforeFirst($testResult[0], $testResult[2]);
                $this->assertSame($testResult[1], $actual);
                $this->assertSame($testResult[1], StringHelper::substringOf($testResult[0], 'b', true, true));
            } else {
                $actual = StringHelper::beforeFirstIgnoreCase($testResult[0], $testResult[2]);
                $this->assertSame($testResult[1], $actual);
                $this->assertSame($testResult[1], StringHelper::substringOf($testResult[0], 'b', true));
            }
        }
    }

    /**
     *
     */
    public function testBeforeLast()
    {
        $testArray = [
            ['', '', 'b', true],
            ['<h1>test</h1>', '', 'b', true],
            ['foo<h1></h1>bar', 'foo<h1></h1>', 'b', true],
            ['<h1></h1> ', '', 'b', true],
            ['</b></b>', '</b></', 'b', true],
            ['öäü<strong>lall</strong>', '', 'b', true],
            [' b<b></b>', ' b<b></', 'b', true],
            ['<b><b>lall</b>', '<b><b>lall</', 'b', true],
            ['</b>lall</b>', '</b>lall</', 'b', true],
            ['[b][/b]', '[b][/', 'b', true],
            ['[B][/B]', '', 'b', true],
            ['κόσμbε ¡-öäü', 'κόσμ', 'b', true],
            ['', '', 'b', false],
            ['<h1>test</h1>', '', 'b', false],
            ['foo<h1></h1>Bar', 'foo<h1></h1>', 'b', false],
            ['foo<h1></h1>bar', 'foo<h1></h1>', 'b', false],
            ['<h1></h1> ', '', 'b', false],
            ['</b></b>', '</b></', 'b', false],
            ['öäü<strong>lall</strong>', '', 'b', false],
            [' b<b></b>', ' b<b></', 'b', false],
            ['<b><b>lall</b>', '<b><b>lall</', 'b', false],
            ['</b>lall</b>', '</b>lall</', 'b', false],
            ['[B][/B]', '[B][/', 'b', false],
            ['κόσμbε ¡-öäü', 'κόσμ', 'b', false],
            ['bκόσμbε', 'bκόσμ', 'b', false],
        ];

        foreach ($testArray as $testResult) {
            if ($testResult[3]) {
                $actual = StringHelper::beforeLast($testResult[0], $testResult[2]);
                $this->assertSame($testResult[1], $actual);
                $this->assertSame($testResult[1], StringHelper::lastSubstringOf($testResult[0], 'b', true, true));
            } else {
                $actual = StringHelper::beforeLastIgnoreCase($testResult[0], $testResult[2]);
                $this->assertSame($testResult[1], $actual);
                $this->assertSame($testResult[1], StringHelper::lastSubstringOf($testResult[0], 'b', true));
            }
        }
    }

    /**
     * @dataProvider betweenDataProvider
     * @param $expected
     * @param $string
     * @param $firstChar
     * @param $secondChar
     * @param $offset
     */
    public function testBetween($expected, $string, $firstChar, $secondChar, $offset = null)
    {
        $actual = StringHelper::between($string, $firstChar, $secondChar, $offset);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider capitalizePersonalNameDataProvider
     * @param $expected
     * @param $string
     */
    public function testCapitalizePersonalName($expected, $string)
    {
        $actual = StringHelper::capitalizePersonalName($string);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider containsAnyDataProvider
     * @param $expected
     * @param $haystack
     * @param $needles
     * @param bool $caseSensitive
     */
    public function testContainsAny($expected, $haystack, $needles, $caseSensitive = true)
    {
        $actual = StringHelper::containsAny($haystack, $needles, $caseSensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testCount()
    {
        $actual = StringHelper::count('Fòô');
        $this->assertSame(3, $actual);
    }

    /**
     * @dataProvider dasherizeDataProvider
     * @param $expected
     * @param $string
     */
    public function testDasherize($expected, $string)
    {
        $actual = StringHelper::dasherize($string);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider ensureLeftDataProvider
     * @param $expected
     * @param $string
     * @param $prepend
     */
    public function testEnsureLeft($expected, $string, $prepend)
    {
        $actual = StringHelper::ensureLeft($string, $prepend);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider ensureRightDataProvider
     * @param $expected
     * @param $string
     * @param $append
     */
    public function testEnsureRight($expected, $string, $append)
    {
        $actual = StringHelper::ensureRight($string, $append);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider hasLowerCaseDataProvider
     * @param $expected
     * @param $string
     */
    public function testHasLowerCase($expected, $string)
    {
        $actual = StringHelper::hasLowerCase($string);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider hasUpperCaseDataProvider
     * @param $expected
     * @param $string
     */
    public function testHasUpperCase($expected, $string)
    {
        $actual = StringHelper::hasUpperCase($string);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testStartsWith()
    {
        $this->assertTrue(StringHelper::startsWith('thisisastring a', 't'));
        $this->assertTrue(StringHelper::startsWith('', ''));
        $this->assertTrue(StringHelper::startsWith('craft cms is awsome', 'craft c'));
        $this->assertTrue(StringHelper::startsWith('😀😘', '😀'));
        $this->assertTrue(StringHelper::startsWith('  ', ' '));

        $this->assertFalse(StringHelper::startsWith('a ball is round', 'b'));
        $this->assertFalse(StringHelper::startsWith('a ball is round', 'ball'));
        $this->assertFalse(StringHelper::startsWith('29*@1*1209)*08231b**!@&712&(!&@', '!&@'));
    }

    /**
     * @dataProvider endsWithDataProvider
     *
     * @param $expected
     * @param $haystack
     * @param $needle
     */
    public function testEndsWith($expected, $haystack, $needle)
    {
        $actual = StringHelper::endsWith($haystack, $needle);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider camelCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testCamelCase($expected, $input)
    {
        $actual = StringHelper::camelCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider containsAllDataProvider
     *
     * @param      $expected
     * @param      $haystack
     * @param      $needle
     * @param bool $caseSensitive
     */
    public function testContainsAll($expected, $haystack, $needle, $caseSensitive = true)
    {
        $actual = StringHelper::containsAll($haystack, $needle, $caseSensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider uppercaseFirstDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testUppercaseFirst($expected, $input)
    {
        $actual = StringHelper::upperCaseFirst($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider indexOfDataProvider
     *
     * @param $expected
     * @param $haystack
     * @param $needle
     */
    public function testIndexOf($expected, $haystack, $needle)
    {
        $actual = StringHelper::indexOf($haystack, $needle);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testSubstringCount()
    {
        $this->assertSame(2, StringHelper::countSubstrings('hello', 'l'));
        $this->assertSame(1, StringHelper::countSubstrings('😀😘', '😘'));
        $this->assertSame(3, StringHelper::countSubstrings('!@#$%^&*()^^', '^'));
        $this->assertSame(4, StringHelper::countSubstrings('    ', ' '));
    }

    /**
     * @dataProvider snakeCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToSnakeCase($expected, $input)
    {
        $actual = StringHelper::toSnakeCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider mb4DataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testIsMb4($expected, $input)
    {
        $actual = StringHelper::containsMb4($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider charsAsArrayDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testCharsAsArray($expected, $input)
    {
        $actual = StringHelper::charsAsArray($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider toAsciiDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToAscii($expected, $input)
    {
        $actual = StringHelper::toAscii($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider firstDataProvider
     *
     * @param $expected
     * @param $input
     * @param $requiredChars
     */
    public function testFirst($expected, $input, $requiredChars)
    {
        $actual = StringHelper::first($input, $requiredChars);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider stripHtmlDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testHtmlStripping($expected, $input)
    {
        $actual = StringHelper::stripHtml($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider uuidDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testIsUUID($expected, $input)
    {
        $actual = StringHelper::isUUID($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collapseWhitespaceDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testWhitespaceCollapse($expected, $input)
    {
        $actual = StringHelper::collapseWhitespace($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider whitespaceDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testIsWhitespace($expected, $input)
    {
        $actual = StringHelper::isWhitespace($input);
        $this->assertSame($expected, $actual);
        $this->assertIsBool($actual);
    }

    /**
     * @dataProvider splitDataProvider
     *
     * @param        $expected
     * @param        $input
     * @param string $splitter
     */
    public function testStringSplit($expected, $input, $splitter = ',')
    {
        $actual = StringHelper::split($input, $splitter);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider delimitDataProvider
     *
     * @param $expected
     * @param $input
     * @param $delimited
     */
    public function testDelimit($expected, $input, $delimited)
    {
        $actual = StringHelper::delimit($input, $delimited);
        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);
    }

    /**
     * @dataProvider randomStringWithCharsDataProvider
     *
     * @param $valid
     * @param int $length
     * @throws \Exception
     */
    public function testRandomStringWithChars($valid, int $length)
    {
        $str = StringHelper::randomStringWithChars($valid, $length);
        $strLen = mb_strlen($str);

        $this->assertSame($length, $strLen);

        // Loop through the string and see if any of the characters aren't on the list of allowed chars.
        for ($i = 0; $i < $strLen; $i++) {
            if (mb_strpos($valid, $str[$i]) === false) {
                $this->fail('Invalid chars');
            }
        }
    }

    /**
     * @dataProvider mb4EncodingDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testMb4Encoding($expected, $input)
    {
        $actual = StringHelper::encodeMb4($input);
        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);

        $this->assertFalse(StringHelper::containsMb4($actual));
    }

    /**
     * @dataProvider convertToUtf8DataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testConvertToUtf8($expected, $input)
    {
        $actual = StringHelper::convertToUtf8($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider encDecDataProvider
     *
     * @param $input
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testEncDec($input)
    {
        $enc = StringHelper::encenc($input);
        $this->assertStringStartsWith('base64:', $enc);
        $this->assertSame($input, StringHelper::decdec($enc));
    }

    /**
     *
     */
    public function testAsciiCharMap()
    {
        $theArray = ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'];
        $this->assertArrayNotHasKey('de', StringHelper::asciiCharMap(false, 'de'));
        $theMap = StringHelper::asciiCharMap(true, 'de');

        foreach ($theArray as $theChar) {
            $this->assertArrayHasKey($theChar, $theMap);
        }
    }

    /**
     *
     */
    public function testUUID()
    {
        $uuid = StringHelper::UUID();
        $this->assertRegExp('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid);
        $this->assertSame(36, strlen($uuid));
    }

    /**
     * @dataProvider toStringDataProvider
     *
     * @param $expected
     * @param $input
     * @param $glue
     */
    public function testToString($expected, $input, $glue = ',')
    {
        $actual = StringHelper::toString($input, $glue);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider randomStringDataProvider
     *
     * @param $length
     * @param $extendedChars
     * @throws \Exception
     */
    public function testRandomString($length = 36, $extendedChars = false)
    {
        $random = StringHelper::randomString($length, $extendedChars);
        $len = strlen($random);
        $this->assertSame($length, $len);

        if ($extendedChars) {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
        } else {
            $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }

        foreach (str_split($random) as $char) {
            $this->assertStringContainsString($char, $validChars);
        }
    }

    /**
     * @dataProvider toPascalCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToPascalCase($expected, $input)
    {
        $actual = StringHelper::toPascalCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider toCamelCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToCamelCase($expected, $input)
    {
        $actual = StringHelper::toCamelCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider toKebabCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToKebabCase($expected, $input)
    {
        $actual = StringHelper::toKebabCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider linesDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testLines($expected, $input)
    {
        $actual = StringHelper::lines($input);
        $this->assertCount($expected, $actual);
    }

    /**
     * @dataProvider toUppercaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToUppercase($expected, $input)
    {
        $actual = StringHelper::toUpperCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider trimDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testTrim($expected, $input)
    {
        $actual = StringHelper::trim($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider toTitleCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToTitleCase($expected, $input)
    {
        $actual = StringHelper::toTitleCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider toLowerCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testToLowerCase($expected, $input)
    {
        $actual = StringHelper::toLowerCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider titleizeDataProvider
     *
     * @param $expected
     * @param $input
     * @param $ignore
     */
    public function testTitleize($expected, $input, $ignore = null)
    {
        $actual = StringHelper::titleize($input, $ignore);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider swapCaseDataProvider
     *
     * @param $expected
     * @param $input
     */
    public function testSwapCase($expected, $input)
    {
        $actual = StringHelper::swapCase($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider substrDataProvider
     *
     * @param      $expected
     * @param      $input
     * @param      $start
     * @param null $length
     */
    public function testSubstr($expected, $input, $start, $length = null)
    {
        $actual = StringHelper::substr($input, $start, $length);
        $this->assertSame($expected, $actual);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function substrDataDataProvider(): array
    {
        return [
            ['st s', 'test s 2 s zw 2', 2, 4],
            [' 😁😂😘', '😂 😁😂😘 😁😂😘 😁', 1, 4],
            ['test CRAF', 'Test test CRAFT cms !@#$%^&  *(abc)', 5, 9],
            ['Craft Cms', 'Craft Cms', 0, 1000],
            ['AFT CMS', 'CRAFT CMS', 2, 1000],
            ['CRAFTCMS', 'CRAFTCMS', 0],
            ['AFTCMS', 'CRAFTCMS', 2],
            ['', '', 2, 5],
            ['', '😘', 1, 5],
            ['#$%  ', '!@#$%  ^&*()', 2, 5],
        ];
    }

    /**
     * @return array
     */
    public function swapCaseDataDataProvider(): array
    {
        return [
            ['TEST S 2 S ZW 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['tEST TEST craft CMS !@#$%^&  *(ABC)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['cRAFT cMS', 'Craft Cms'],
            ['craft cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 aLPHAn nUMERIC', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function toTitleCaseDataProvider(): array
    {
        return [
            ['Test S 2 S Zw 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['Test Test Craft Cms !@#$%^&  *(Abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['Craft Cms', 'Craft Cms'],
            ['Craft Cms', 'CRAFT CMS'],
            ['Craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 Alphan Numeric', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function toLowerCaseDataProvider(): array
    {
        return [
            ['test s 2 s zw 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['test test craft cms !@#$%^&  *(abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craft cms', 'Craft Cms'],
            ['craft cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 alphan numeric', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function indexOfDataProvider(): array
    {
        return [
            [2, 'thisisstring', 'is'],
            [6, 'craft cms', 'cms'],
            [1, '😀😘', '😘'],
            [2, '/@#$%^&*', '#'],
            [0, 'hello, people', 'he'],
            [false, 'some string', 'a needle'],
            [false, '', '']
        ];
    }

    /**
     * @return array
     */
    public function camelCaseDataProvider(): array
    {
        return [
            ['craftCms', 'Craft Cms'],
            ['cRAFTCMS', 'CRAFT CMS'],
            ['cRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['!@#$%^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function endsWithDataProvider(): array
    {
        return [
            [true, 'thisisastring a', 'a'],
            [true, '', ''],
            [true, 'craft cms is awsome', 's awsome'],
            [true, '', ''],
            [true, '😀😘', '😘'],
            [true, '😀😘', '😘'],
            [true, '    ', ' '],
            [true, '29*@1*1209)*08231b**!@&712&(!&@', '!&@'],
            [false, 'a ball is round', 'square'],
            [false, 'a ball is round', 'ball'],
        ];
    }

    /**
     * @return array
     */
    public function uppercaseFirstDataProvider(): array
    {
        return [
            ['Craftcms', 'craftcms'],
            ['2craftcms', '2craftcms'],
            [' craftcms', ' craftcms'],
            [' ', ' ']
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function uuidDataProvider(): array
    {
        return [
            [true, StringHelper::UUID()],
            [true, 'c3d6a75d-5b98-4048-8106-8cc2de4af159'],
            [true, 'c74e8f78-c052-4978-b0e8-77a307f7b946'],
            [true, '469e6ed2-f270-458a-a80e-173821fee715'],
            [false, '00000000-0000-0000-0000-000000000000'],
            [false, StringHelper::UUID() . StringHelper::UUID()],
            [false, 'abc'],
            [false, '123'],
            [false, ''],
            [false, ' '],
            [false, '!@#$%^&*()'],
            [false, '469e6ed2-🎧𢵌😀😘-458a-a80e-173821fee715'],
            [false, '&*%!$^!#-5b98-4048-8106-8cc2de4af159']
        ];
    }

    /**
     * @return array
     */
    public function stripHtmlDataProvider(): array
    {
        return [
            ['hello', '<p>hello</p>'],
            ['hello', '<>hello</>'],
            ['hello', '<script src="https://">hello</script>'],
            ['', '<link src="#">'],
            ['hello', '<random-tag src="#">hello</random-tag>'],
            ['hellohellohello', '<div>hello<p>hello</p>hello</div>'],
        ];
    }

    /**
     * @return array
     */
    public function firstDataProvider(): array
    {
        return [
            ['', '', 1],
            ['qwertyuiopas', 'qwertyuiopasdfghjklzxcvbnm', 12],
            ['QWE', 'QWERTYUIOPASDFGHJKLZXCVBNM', 3],
            ['12', '123456789', 2],
            ['!@#$%^', '!@#$%^', 100],
            ['🎧𢵌', '🎧𢵌😀😘⛄', 2],
        ];
    }

    /**
     * @return array
     */
    public function toAsciiDataProvider(): array
    {
        return [
            ['', ''],
            ['abc', 'abc'],
            ['123', '123'],
            ['!@#$%^', '!@#$%^'],
            ['', '🎧𢵌😀😘⛄'],
            ['abc123', '🎧𢵌😀abc😘123⛄']
        ];
    }

    /**
     * @return array
     */
    public function charsAsArrayDataProvider(): array
    {
        return [
            [[], ''],
            [['a', 'b', 'c'], 'abc'],
            [['1', '2', '3'], '123'],
            [['!', '@', '#', '$', '%', '^'], '!@#$%^'],
            [['🎧', '𢵌', '😀', '😘', '⛄'], '🎧𢵌😀😘⛄'],
        ];
    }

    /**
     * @return array
     */
    public function mb4DataProvider(): array
    {
        return [
            [true, '😀😘'],
            [true, 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890😘'],
            [true, '!@#$%^&*()_🎧'],
            [true, '!@#$%^&*(𢵌)_'],
            [false, 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890'],
            [false, '!@#$%^&*()_'],
            [false, '⛄'],
            [false, ''],
            [true, '𨳊']
        ];
    }

    /**
     * @return array
     */
    public function snakeCaseDataProvider(): array
    {
        return [
            ['c_r_a_f_t_c_m_s', 'CRAFT CMS'],
            ['c_r_a_f_t_c_m_s', 'CRAFTCMS'],
            ['', ''],
            ['i_😘_u', 'I 😘 U'],
            ['2_2_alpha_n_numeric', '22 AlphaN Numeric'],
        ];
    }

    /**
     * @return array
     */
    public function delimitDataProvider(): array
    {
        return [
            ['', '    ', '|'],
            ['hello|iam|astring', 'HelloIamAstring', '|'],
            ['😀😁😂🤣😃😄😅😆', '😀😁😂🤣😃😄😅😆', '|'],
            ['hello iam astring', 'HelloIamAstring', ' '],
            ['hello!@#iam!@#astring', 'HelloIamAstring', '!@#'],
            ['hello😀😁😂iam😀😁😂astring', 'HelloIamAstring', '😀😁😂'],
            ['hello😀😁😂iam😀😁😂a2string', 'HelloIamA2string', '😀😁😂'],

        ];
    }

    /**
     * @return array
     */
    public function splitDataProvider(): array
    {
        return [
            [['22', '23'], '22, 23'],
            [['ab', 'cd'], 'ab,cd'],
            [['22', '23'], '22,23, '],
            [['22', '23'], '22| 23', '|'],
            [['22,', '23'], '22,/ 23', '/'],
            [['22', '23'], '22😀23', '😀'],
        ];
    }

    /**
     * @return array
     */
    public function whitespaceDataProvider(): array
    {
        return [
            [true, ''],
            [true, ' '],
            [true, '                                           '],
            [false, 'qwe rty     uio   pasd'],
            [false, 'Q                     W E'],
            [false, '    12345   67     89     '],
            [false, '! @     #$     %       ^'],
            [false, '🎧𢵌       😀😘⛄       '],
            [false, 'craftcms'],
            [false, '/@#$%^&*'],
            [false, 'hello,people'],
        ];
    }

    /**
     * @return array
     */
    public function collapseWhitespaceDataProvider(): array
    {
        return [
            ['', '  '],
            ['', '                                           '],
            ['qwe rty uio pasd', 'qwe rty     uio   pasd'],
            ['Q W E', 'Q                     W E'],
            ['12345 67 89', '    12345   67     89     '],
            ['! @ #$ % ^', '! @     #$     %       ^'],
            ['🎧𢵌 😀😘⛄', '🎧𢵌       😀😘⛄       '],
        ];
    }

    /**
     * @return array
     */
    public function randomStringWithCharsDataProvider(): array
    {
        return [
            ['asdfghjklxcvbnmqwertyuiop', 10],
            ['1234567890', 22],
            ['!@#$%^&*()_{}|:"<>?', 0],
            ['!@#$%^&*()_{}|:"<>?', 8],
            ['                           ', 8],
            'tabs' => ['              ', 4],
            ['asdfghjklxcvbnmqwertyuiop', 10]
        ];
    }

    /**
     * @return array
     */
    public function mb4EncodingDataProvider(): array
    {
        return [
            ['&#x1f525;', '🔥'],
            ['&#x1f525;', '&#x1f525;'],
            ['&#x1f1e6;&#x1f1fa;', '🇦🇺'],
            ['&#x102cd;', '𐋍'],
            ['asdfghjklqwertyuiop1234567890!@#$%^&*()_+', 'asdfghjklqwertyuiop1234567890!@#$%^&*()_+'],
            ['&#x102cd;&#x1f1e6;&#x1f1fa;&#x1f525;', '𐋍🇦🇺🔥'],
            'ensure-non-mb4-is-ignored' => ['&#x102cd;1234567890&#x1f1e6;&#x1f1fa; &#x1f525;', '𐋍1234567890🇦🇺 🔥']
        ];
    }

    /**
     * @return array
     */
    public function convertToUtf8DataProvider(): array
    {
        return [
            ['κόσμε', 'κόσμε'],
            ['\x74\x65\x73\x74', '\x74\x65\x73\x74'],
            ['craftcms', 'craftcms'],
            ['😂😁', '😂😁'],
            ['Foo © bar 𝌆 baz ☃ qux', 'Foo © bar 𝌆 baz ☃ qux'],
            ['İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±', 'İnanç Esasları" shown as "Ä°nanÃ§ EsaslarÄ±']
        ];
    }

    /**
     * @return array
     */
    public function encDecDataProvider(): array
    {
        return [
            ['1234567890asdfghjkl'],
            ['😂😁'],
            ['!@#$%^&*()_+{}|:"<>?']
        ];
    }

    /**
     * @return array
     */
    public function afterFirstDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['ar', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['></b>', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            ['<b></b>', ' b<b></b>', 'b', true],
            ['><b>lall</b>', '<b><b>lall</b>', 'b', true],
            ['>lall</b>', '</b>lall</b>', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['][/b]', '[b][/b]', 'b', true],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', true],
            ['κόσμbε', 'bκόσμbε', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['ar', 'foo<h1></h1>Bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['></b>', '</B></b>', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            ['></b>B', ' <b></b>B', 'B', false],
            ['><b>lall</b>', '<b><b>lall</b>', 'b', false],
            ['>lall</b>', '</b>lall</b>', 'b', false],
            ['][/B]', '[B][/B]', 'b', false],
            ['][/b]', '[B][/b]', 'B', false],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', false],
            ['κόσμbε', 'bκόσμbε', 'B', false],
        ];
    }

    /**
     * @return array
     */
    public function afterLastDataProvider(): array
    {
        return [
            ['', '', 'b', true],
            ['', '<h1>test</h1>', 'b', true],
            ['ar', 'foo<h1></h1>bar', 'b', true],
            ['', '<h1></h1> ', 'b', true],
            ['>', '</b></b>', 'b', true],
            ['', 'öäü<strong>lall</strong>', 'b', true],
            ['>', ' b<b></b>', 'b', true],
            ['>', '<b><b>lall</b>', 'b', true],
            ['>', '</b>lall</b>', 'b', true],
            [']', '[b][/b]', 'b', true],
            ['', '[B][/B]', 'b', true],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', true],
            ['', '', 'b', false],
            ['', '<h1>test</h1>', 'b', false],
            ['ar', 'foo<h1></h1>bar', 'b', false],
            ['ar', 'foo<h1></h1>Bar', 'b', false],
            ['', '<h1></h1> ', 'b', false],
            ['', 'öäü<strong>lall</strong>', 'b', false],
            ['>', ' b<b></b>', 'b', false],
            ['>', '<b><b>lall</b>', 'b', false],
            ['>', '<b><B>lall</B>', 'b', false],
            [']', '[b][/b]', 'b', false],
            ['ε ¡-öäü', 'κόσμbε ¡-öäü', 'b', false],
        ];
    }

    /**
     * @return array
     */
    public function toStringDataProvider(): array
    {
        return [
            ['test', 'test'],
            ['', new stdClass()],
            ['ima string', new ToString('ima string')],
            ['t,e,s,t', ['t', 'e', 's', 't']],
            ['t|e|s|t', ['t', 'e', 's', 't'], '|'],
        ];
    }

    /**
     * @return array
     */
    public function randomStringDataProvider(): array
    {
        return [
            [],
            [50, false],
            [55, true],
        ];
    }

    /**
     * @return array
     */
    public function toPascalCaseDataProvider(): array
    {
        return [
            ['TestS2SZw2', 'test s 2 s zw 2'],
            ['', '😂 😁'],
            ['TestTestCraftCmsAbc', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['CraftCms', 'Craft Cms'],
            ['CraftCms', 'CRAFT CMS'],
            ['Craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function toCamelCaseDataProvider(): array
    {
        return [
            ['testS2SZw2', 'test s 2 s zw 2'],
            ['iAmSo😂😁!', 'I am so 😂 😁!'],
            ['testTestCRAFTCms!@#$%^&*(abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craftCms', 'Craft Cms'],
            ['cRAFTCMS', 'CRAFT CMS'],
            ['cRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['!@#$%^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function toKebabCaseDataProvider(): array
    {
        return [
            ['test-s-2-s-zw-2', 'test s 2 s zw 2'],
            ['test-s-0-s-zw-2', 'test s 0 s zw 2'],
            ['', '😂 😁'],
            ['test-test-craft-cms-abc', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craft-cms', 'Craft Cms'],
            ['craft-cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22-alpha-n-numeric', '22 AlphaN Numeric'],
            ['', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function linesDataProvider(): array
    {
        return [
            [
                4, 'test
             
             
             test'
            ],
            [1, 'test <br> test'],
            [1, 'thesearetabs       notspaces'],
            [
                2, '😂
            😁'
            ],
            [
                11, '
            
            
            
            
            
            
            
            
            
            '
            ]
        ];
    }

    /**
     * @return array
     */
    public function toUppercaseDataProvider(): array
    {
        return [
            ['TEST S 2 S ZW 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['TEST TEST CRAFT CMS !@#$%^&  *(ABC)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['CRAFT CMS', 'Craft Cms'],
            ['CRAFT CMS', 'CRAFT CMS'],
            ['CRAFTCMS', 'CRAFTCMS'],
            ['', ''],
            ['😘', '😘'],
            ['22 ALPHAN NUMERIC', '22 AlphaN Numeric'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function trimDataProvider(): array
    {
        return [
            ['😂 😁', '😂 😁 '],
            ['', ''],
            ['😘', '😘'],
            ['!@#$%  ^&*()', '!@#$%  ^&*()'],
            ['\x09Example string\x0A', '\x09Example string\x0A'],
            ['\t\tThese are a few words :) ...', '\t\tThese are a few words :) ...  ']
        ];
    }

    /**
     * @return array
     */
    public function appendDataProvider(): array
    {
        return [
            ['foobar', 'foo', 'bar'],
            ['fòôbàř', 'fòô', 'bàř'],
        ];
    }

    /**
     * @return array
     */
    public function atDataProvider(): array
    {
        return [
            ['f', 'foo bar', 0],
            ['o', 'foo bar', 1],
            ['r', 'foo bar', 6],
            ['', 'foo bar', 7],
            ['f', 'fòô bàř', 0],
            ['ò', 'fòô bàř', 1],
            ['ř', 'fòô bàř', 6],
            ['', 'fòô bàř', 7],
        ];
    }

    /**
     * @return array
     */
    public function betweenDataProvider(): array
    {
        return [
            ['', 'foo', '{', '}'],
            ['', '{foo', '{', '}'],
            ['foo', '{foo}', '{', '}'],
            ['{foo', '{{foo}', '{', '}'],
            ['', '{}foo}', '{', '}'],
            ['foo', '}{foo}', '{', '}'],
            ['foo', 'A description of {foo} goes here', '{', '}'],
            ['bar', '{foo} and {bar}', '{', '}', 1],
            ['', 'fòô', '{', '}', 0],
            ['', '{fòô', '{', '}', 0],
            ['fòô', '{fòô}', '{', '}', 0],
            ['{fòô', '{{fòô}', '{', '}', 0],
            ['', '{}fòô}', '{', '}', 0],
            ['fòô', '}{fòô}', '{', '}', 0],
            ['fòô', 'A description of {fòô} goes here', '{', '}', 0],
            ['bàř', '{fòô} and {bàř}', '{', '}', 1],
        ];
    }

    /**
     * @return array
     */
    public function camelizeDataProvider(): array
    {
        return [
            ['camelCase', 'CamelCase'],
            ['camelCase', 'Camel-Case'],
            ['camelCase', 'camel case'],
            ['camelCase', 'camel -case'],
            ['camelCase', 'camel - case'],
            ['camelCase', 'camel_case'],
            ['camelCTest', 'camel c test'],
            ['stringWith1Number', 'string_with1number'],
            ['stringWith22Numbers', 'string-with-2-2 numbers'],
            ['dataRate', 'data_rate'],
            ['backgroundColor', 'background-color'],
            ['yesWeCan', 'yes_we_can'],
            ['mozSomething', '-moz-something'],
            ['carSpeed', '_car_speed_'],
            ['serveHTTP', 'ServeHTTP'],
            ['1Camel2Case', '1camel2case'],
            ['camelΣase', 'camel σase', 'UTF-8'],
            ['στανιλCase', 'Στανιλ case', 'UTF-8'],
            ['σamelCase', 'σamel  Case', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function capitalizePersonalNameDataProvider(): array
    {
        return [
            ['Marcus Aurelius', 'marcus aurelius'],
            ['Torbjørn Færøvik', 'torbjørn færøvik'],
            ['Jaap de Hoop Scheffer', 'jaap de hoop scheffer'],
            ['K. Anders Ericsson', 'k. anders ericsson'],
            ['Per-Einar', 'per-einar'],
            [
                'Line Break',
                'line
             break',
            ],
            ['ab', 'ab'],
            ['af', 'af'],
            ['al', 'al'],
            ['and', 'and'],
            ['ap', 'ap'],
            ['bint', 'bint'],
            ['binte', 'binte'],
            ['da', 'da'],
            ['de', 'de'],
            ['del', 'del'],
            ['den', 'den'],
            ['der', 'der'],
            ['di', 'di'],
            ['dit', 'dit'],
            ['ibn', 'ibn'],
            ['la', 'la'],
            ['mac', 'mac'],
            ['nic', 'nic'],
            ['of', 'of'],
            ['ter', 'ter'],
            ['the', 'the'],
            ['und', 'und'],
            ['van', 'van'],
            ['von', 'von'],
            ['y', 'y'],
            ['zu', 'zu'],
            ['Bashar al-Assad', 'bashar al-assad'],
            ["d'Name", "d'Name"],
            ['ffName', 'ffName'],
            ["l'Name", "l'Name"],
            ['macDuck', 'macDuck'],
            ['mcDuck', 'mcDuck'],
            ['nickMick', 'nickMick'],
        ];
    }

    /**
     * @return array
     */
    public function charsDataProvider(): array
    {
        return [
            [[], ''],
            [['T', 'e', 's', 't'], 'Test'],
            [['F', 'ò', 'ô', ' ', 'B', 'à', 'ř'], 'Fòô Bàř', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function collapseWhitespaceDataProvider(): array
//    {
//        return [
//            ['foo bar', '  foo   bar  '],
//            ['test string', 'test string'],
//            ['Ο συγγραφέας', '   Ο     συγγραφέας  '],
//            ['123', ' 123 '],
//            ['', ' ', 'UTF-8'], // no-break space (U+00A0)
//            ['', '           ', 'UTF-8'], // spaces U+2000 to U+200A
//            ['', ' ', 'UTF-8'], // narrow no-break space (U+202F)
//            ['', ' ', 'UTF-8'], // medium mathematical space (U+205F)
//            ['', '　', 'UTF-8'], // ideographic space (U+3000)
//            ['1 2 3', '  1  2  3　　', 'UTF-8'],
//            ['', ' '],
//            ['', ''],
//        ];
//    }

    /**
     * @return array
     */
    public function containsAllDataProvider(): array
    {
        // One needle
        $singleNeedle = array_map(
            static function($array) {
                $array[2] = [$array[2]];
                return $array;
            },
            $this->containsDataProvider()
        );
        $provider = [
            // One needle
            [false, 'Str contains foo bar', []],
            [false, 'Str contains foo bar', ['']],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας'], 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true, 'UTF-8'],
            [false, 'Str contains foo bar', ['Foo', 'bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar']],
            [false, 'Str contains foo bar', ['foo bar ', 'bar']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true, 'UTF-8'],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false, 'UTF-8'],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba'], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false, 'UTF-8'],
        ];

        return array_merge($singleNeedle, $provider);
    }

    /**
     * @return array
     */
    public function containsAnyDataProvider(): array
    {
        // One needle
        $singleNeedle = array_map(
            static function ($array) {
                $array[2] = [$array[2]];

                return $array;
            },

            $this->containsDataProvider()
        );

        $provider = [
            // No needles
            [false, 'Str contains foo bar', []],
            // Multiple needles
            [true, 'Str contains foo bar', ['foo', 'bar']],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*', '&^%']],
            [true, 'Ο συγγραφέας είπε', ['συγγρ', 'αφέας'], 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å´¥', '©'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['å˚ ', '∆'], true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['øœ', '¬'], true, 'UTF-8'],
            [false, 'Str contains foo bar', ['Foo', 'Bar']],
            [false, 'Str contains foo bar', ['foobar', 'bar ']],
            [false, 'Str contains foo bar', ['foo bar ', '  foo']],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', '  συγγραφ '], true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßå˚', ' ß '], true, 'UTF-8'],
            [true, 'Str contains foo bar', ['Foo bar', 'bar'], false],
            [true, '12398!@(*%!@# @!%#*&^%', [' @!%#*&^%', '*&^%'], false],
            [true, 'Ο συγγραφέας είπε', ['ΣΥΓΓΡΑΦΈΑΣ', 'ΑΦΈΑ'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å´¥©', '¥©'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['Å˚ ∆', ' ∆'], false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ['ØŒ¬', 'Œ'], false, 'UTF-8'],
            [false, 'Str contains foo bar', ['foobar', 'none'], false],
            [false, 'Str contains foo bar', ['foo bar ', ' ba '], false],
            [false, 'Ο συγγραφέας είπε', ['  συγγραφέας ', ' ραφέ '], false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', [' ßÅ˚', ' Å˚ '], false, 'UTF-8'],
        ];

        return array_merge($singleNeedle, $provider);
    }

    /**
     * @return array
     */
    public function containsDataProvider(): array
    {
        return [
            [true, 'Str contains foo bar', 'foo bar'],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%'],
            [true, 'Ο συγγραφέας είπε', 'συγγραφέας', 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å´¥©', true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'å˚ ∆', true, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'øœ¬', true, 'UTF-8'],
            [false, 'Str contains foo bar', 'Foo bar'],
            [false, 'Str contains foo bar', 'foobar'],
            [false, 'Str contains foo bar', 'foo bar '],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', true, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßå˚', true, 'UTF-8'],
            [true, 'Str contains foo bar', 'Foo bar', false],
            [true, '12398!@(*%!@# @!%#*&^%', ' @!%#*&^%', false],
            [true, 'Ο συγγραφέας είπε', 'ΣΥΓΓΡΑΦΈΑΣ', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å´¥©', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'Å˚ ∆', false, 'UTF-8'],
            [true, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'ØŒ¬', false, 'UTF-8'],
            [false, 'Str contains foo bar', 'foobar', false],
            [false, 'Str contains foo bar', 'foo bar ', false],
            [false, 'Ο συγγραφέας είπε', '  συγγραφέας ', false, 'UTF-8'],
            [false, 'å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', ' ßÅ˚', false, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function countSubstrDataProvider(): array
    {
        return [
            [0, '', 'foo'],
            [0, 'foo', 'bar'],
            [1, 'foo bar', 'foo'],
            [2, 'foo bar', 'o'],
            [0, '', 'fòô', 'UTF-8'],
            [0, 'fòô', 'bàř', 'UTF-8'],
            [1, 'fòô bàř', 'fòô', 'UTF-8'],
            [2, 'fôòô bàř', 'ô', 'UTF-8'],
            [0, 'fÔÒÔ bàř', 'ô', 'UTF-8'],
            [0, 'foo', 'BAR', false],
            [1, 'foo bar', 'FOo', false],
            [2, 'foo bar', 'O', false],
            [1, 'fòô bàř', 'fÒÔ', false, 'UTF-8'],
            [2, 'fôòô bàř', 'Ô', false, 'UTF-8'],
            [2, 'συγγραφέας', 'Σ', false, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function dasherizeDataProvider(): array
    {
        return [
            ['test-case', 'testCase'],
            ['test-case', 'Test-Case'],
            ['test-case', 'test case'],
            ['-test-case', '-test -case'],
            ['test-case', 'test - case'],
            ['test-case', 'test_case'],
            ['test-c-test', 'test c test'],
            ['test-d-case', 'TestDCase'],
            ['test-c-c-test', 'TestCCTest'],
            ['string-with1number', 'string_with1number'],
            ['string-with-2-2-numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['data-rate', 'dataRate'],
            ['car-speed', 'CarSpeed'],
            ['yes-we-can', 'yesWeCan'],
            ['background-color', 'backgroundColor'],
            ['dash-σase', 'dash Σase'],
            ['στανιλ-case', 'Στανιλ case'],
            ['σash-case', 'Σash  Case'],
        ];
    }

    /**
     * @return array
     */
//    public function delimitDataProvider(): array
//    {
//        return [
//            ['test*case', 'testCase', '*'],
//            ['test&case', 'Test-Case', '&'],
//            ['test#case', 'test case', '#'],
//            ['test**case', 'test -case', '**'],
//            ['~!~test~!~case', '-test - case', '~!~'],
//            ['test*case', 'test_case', '*'],
//            ['test%c%test', '  test c test', '%'],
//            ['test+u+case', 'TestUCase', '+'],
//            ['test=c=c=test', 'TestCCTest', '='],
//            ['string#>with1number', 'string_with1number', '#>'],
//            ['1test2case', '1test2case', '*'],
//            ['test ύα σase', 'test Σase', ' ύα ', 'UTF-8'],
//            ['στανιλαcase', 'Στανιλ case', 'α', 'UTF-8'],
//            ['σashΘcase', 'Σash  Case', 'Θ', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function endsWithAnyDataProvider(): array
    {
        return [
            [true, 'foo bars', ['foo', 'o bars']],
            [true, 'FOO bars', ['foo', 'o bars'], false],
            [true, 'FOO bars', ['foo', 'o BARs'], false],
            [true, 'FÒÔ bàřs', ['foo', 'ô bàřs'], false, 'UTF-8'],
            [true, 'fòô bàřs', ['foo', 'ô BÀŘs'], false, 'UTF-8'],
            [false, 'foo bar', ['foo']],
            [false, 'foo bar', ['foo', 'foo bars']],
            [false, 'FOO bar', ['foo', 'foo bars']],
            [false, 'FOO bars', ['foo', 'foo BARS']],
            [false, 'FÒÔ bàřs', ['fòô', 'fòô bàřs'], true, 'UTF-8'],
            [false, 'fòô bàřs', ['fòô', 'fòô BÀŘS'], true, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function endsWithDataProvider(): array
//    {
//        return [
//            [true, 'foo bars', 'o bars'],
//            [true, 'FOO bars', 'o bars', false],
//            [true, 'FOO bars', 'o BARs', false],
//            [true, 'FÒÔ bàřs', 'ô bàřs', false, 'UTF-8'],
//            [true, 'fòô bàřs', 'ô BÀŘs', false, 'UTF-8'],
//            [false, 'foo bar', 'foo'],
//            [false, 'foo bar', 'foo bars'],
//            [false, 'FOO bar', 'foo bars'],
//            [false, 'FOO bars', 'foo BARS'],
//            [false, 'FÒÔ bàřs', 'fòô bàřs', true, 'UTF-8'],
//            [false, 'fòô bàřs', 'fòô BÀŘS', true, 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function ensureLeftDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 'f'],
            ['foobar', 'foobar', 'foo'],
            ['foo/foobar', 'foobar', 'foo/'],
            ['http://foobar', 'foobar', 'http://'],
            ['http://foobar', 'http://foobar', 'http://'],
            ['fòôbàř', 'fòôbàř', 'f',],
            ['fòôbàř', 'fòôbàř', 'fòô'],
            ['fòô/fòôbàř', 'fòôbàř', 'fòô/'],
            ['http://fòôbàř', 'fòôbàř', 'http://'],
            ['http://fòôbàř', 'http://fòôbàř', 'http://'],
        ];
    }

    /**
     * @return array
     */
    public function ensureRightDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 'r'],
            ['foobar', 'foobar', 'bar'],
            ['foobar/bar', 'foobar', '/bar'],
            ['foobar.com/', 'foobar', '.com/'],
            ['foobar.com/', 'foobar.com/', '.com/'],
            ['fòôbàř', 'fòôbàř', 'ř', 'UTF-8'],
            ['fòôbàř', 'fòôbàř', 'bàř', 'UTF-8'],
            ['fòôbàř/bàř', 'fòôbàř', '/bàř', 'UTF-8'],
            ['fòôbàř.com/', 'fòôbàř', '.com/', 'UTF-8'],
            ['fòôbàř.com/', 'fòôbàř.com/', '.com/', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function escapeDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof &lt;3', 'raboof <3'],
            ['řàbôòf&lt;foo&lt;lall&gt;&gt;&gt;', 'řàbôòf<foo<lall>>>'],
            ['řàb &lt;ô&gt;òf', 'řàb <ô>òf'],
            ['&lt;∂∆ onerro=&quot;alert(xss)&quot;&gt; ˚åß', '<∂∆ onerro="alert(xss)"> ˚åß'],
            ['&#039;œ … &#039;’)', '\'œ … \'’)'],
        ];
    }

    /**
     * @return array
     */
//    public function firstDataProvider(): array
//    {
//        return [
//            ['', 'foo bar', -5],
//            ['', 'foo bar', 0],
//            ['f', 'foo bar', 1],
//            ['foo', 'foo bar', 3],
//            ['foo bar', 'foo bar', 7],
//            ['foo bar', 'foo bar', 8],
//            ['', 'fòô bàř', -5, 'UTF-8'],
//            ['', 'fòô bàř', 0, 'UTF-8'],
//            ['f', 'fòô bàř', 1, 'UTF-8'],
//            ['fòô', 'fòô bàř', 3, 'UTF-8'],
//            ['fòô bàř', 'fòô bàř', 7, 'UTF-8'],
//            ['fòô bàř', 'fòô bàř', 8, 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function hasLowerCaseDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'foobar'],
            [false, 'FOO BAR'],
            [true, 'fOO BAR'],
            [true, 'foO BAR'],
            [true, 'FOO BAr'],
            [true, 'Foobar'],
            [false, 'FÒÔBÀŘ', 'UTF-8'],
            [true, 'fòôbàř', 'UTF-8'],
            [true, 'fòôbàř2', 'UTF-8'],
            [true, 'Fòô bàř', 'UTF-8'],
            [true, 'fòôbÀŘ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function hasUpperCaseDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'FOOBAR'],
            [false, 'foo bar'],
            [true, 'Foo bar'],
            [true, 'FOo bar'],
            [true, 'foo baR'],
            [true, 'fOOBAR'],
            [false, 'fòôbàř', 'UTF-8'],
            [true, 'FÒÔBÀŘ', 'UTF-8'],
            [true, 'FÒÔBÀŘ2', 'UTF-8'],
            [true, 'fÒÔ BÀŘ', 'UTF-8'],
            [true, 'FÒÔBàř', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function htmlDecodeDataProvider(): array
    {
        return [
            ['&', '&amp;'],
            ['"', '&quot;'],
            ["'", '&#039;', \ENT_QUOTES],
            ['<', '&lt;'],
            ['>', '&gt;'],
        ];
    }

    /**
     * @return array
     */
    public function htmlEncodeDataProvider(): array
    {
        return [
            ['&amp;', '&'],
            ['&quot;', '"'],
            ['&#039;', "'", \ENT_QUOTES],
            ['&lt;', '<'],
            ['&gt;', '>'],
        ];
    }

    /**
     * @return array
     */
    public function humanizeDataProvider(): array
    {
        return [
            ['Author', 'author_id'],
            ['Test user', ' _test_user_'],
            ['Συγγραφέας', ' συγγραφέας_id ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function indexOfLastDataProvider(): array
    {
        return [
            [6, 'foo & bar', 'bar'],
            [6, 'foo & bar', 'bar', 0],
            [false, 'foo & bar', 'baz'],
            [false, 'foo & bar', 'baz', 0],
            [12, 'foo & bar & foo', 'foo', 0],
            [0, 'foo & bar & foo', 'foo', -5],
            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
            [12, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
            [0, 'fòô & bàř & fòô', 'fòô', -5, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function indexOfLastIgnoreCaseDataProvider(): array
    {
        return [
            [6, 'foo & bar', 'Bar'],
            [6, 'foo & bar', 'bAr', 0],
            [false, 'foo & bar', 'baZ'],
            [false, 'foo & bar', 'baZ', 0],
            [12, 'foo & bar & foo', 'fOo', 0],
            [0, 'foo & bar & foo', 'fOO', -5],
            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
            [12, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
            [0, 'fòô & bàř & fòô', 'fòÔ', -5, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function indexOfIgnoreCaseDataProvider(): array
    {
        return [
            [6, 'foo & bar', 'Bar'],
            [6, 'foo & bar', 'bar', 0],
            [false, 'foo & bar', 'Baz'],
            [false, 'foo & bar', 'bAz', 0],
            [0, 'foo & bar & foo', 'foO', 0],
            [12, 'foo & bar & foo', 'fOO', 5],
            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
            [0, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
            [12, 'fòô & bàř & fòô', 'fòÔ', 5, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function indexOfDataProvider(): array
//    {
//        return [
//            [6, 'foo & bar', 'bar'],
//            [6, 'foo & bar', 'bar', 0],
//            [false, 'foo & bar', 'baz'],
//            [false, 'foo & bar', 'baz', 0],
//            [0, 'foo & bar & foo', 'foo', 0],
//            [12, 'foo & bar & foo', 'foo', 5],
//            [6, 'fòô & bàř', 'bàř', 0, 'UTF-8'],
//            [false, 'fòô & bàř', 'baz', 0, 'UTF-8'],
//            [0, 'fòô & bàř & fòô', 'fòô', 0, 'UTF-8'],
//            [12, 'fòô & bàř & fòô', 'fòô', 5, 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function insertDataProvider(): array
    {
        return [
            ['foo bar', 'oo bar', 'f', 0],
            ['foo bar', 'f bar', 'oo', 1],
            ['f bar', 'f bar', 'oo', 20],
            ['foo bar', 'foo ba', 'r', 6],
            ['fòôbàř', 'fòôbř', 'à', 4, 'UTF-8'],
            ['fòô bàř', 'òô bàř', 'f', 0, 'UTF-8'],
            ['fòô bàř', 'f bàř', 'òô', 1, 'UTF-8'],
            ['fòô bàř', 'fòô bà', 'ř', 6, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isAlphaDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'foobar2'],
            [true, 'fòôbàř', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbàř2', 'UTF-8'],
            [true, 'ҠѨњфгШ', 'UTF-8'],
            [false, 'ҠѨњ¨ˆфгШ', 'UTF-8'],
            [true, '丹尼爾', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isAlphanumericDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar1'],
            [false, 'foo bar'],
            [false, 'foobar2"'],
            [false, "\nfoobar\n"],
            [true, 'fòôbàř1', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbàř2"', 'UTF-8'],
            [true, 'ҠѨњфгШ', 'UTF-8'],
            [false, 'ҠѨњ¨ˆфгШ', 'UTF-8'],
            [true, '丹尼爾111', 'UTF-8'],
            [true, 'دانيال1', 'UTF-8'],
            [false, 'دانيال1 ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isBase64DataProvider(): array
    {
        return [
            [false, ' '],
            [false, ''],
            [true, \base64_encode('FooBar')],
            [true, \base64_encode(' ')],
            [true, \base64_encode('FÒÔBÀŘ')],
            [true, \base64_encode('συγγραφέας')],
            [false, 'Foobar'],
        ];
    }

    /**
     * @return array
     */
    public function isBlankDataProvider(): array
    {
        return [
            [true, ''],
            [true, ' '],
            [true, "\n\t "],
            [true, "\n\t  \v\f"],
            [false, "\n\t a \v\f"],
            [false, "\n\t ' \v\f"],
            [false, "\n\t 2 \v\f"],
            [true, '', 'UTF-8'],
            [true, ' ', 'UTF-8'], // no-break space (U+00A0)
            [true, '           ', 'UTF-8'], // spaces U+2000 to U+200A
            [true, ' ', 'UTF-8'], // narrow no-break space (U+202F)
            [true, ' ', 'UTF-8'], // medium mathematical space (U+205F)
            [true, '　', 'UTF-8'], // ideographic space (U+3000)
            [false, '　z', 'UTF-8'],
            [false, '　1', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isHexadecimalDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'abcdef'],
            [true, 'ABCDEF'],
            [true, '0123456789'],
            [true, '0123456789AbCdEf'],
            [false, '0123456789x'],
            [false, 'ABCDEFx'],
            [true, 'abcdef', 'UTF-8'],
            [true, 'ABCDEF', 'UTF-8'],
            [true, '0123456789', 'UTF-8'],
            [true, '0123456789AbCdEf', 'UTF-8'],
            [false, '0123456789x', 'UTF-8'],
            [false, 'ABCDEFx', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isJsonDataProvider(): array
    {
        return [
            [false, ''],
            [false, '  '],
            [false, 'null'],
            [false, 'true'],
            [false, 'false'],
            [true, '[]'],
            [true, '{}'],
            [false, '123'],
            [true, '{"foo": "bar"}'],
            [false, '{"foo":"bar",}'],
            [false, '{"foo"}'],
            [true, '["foo"]'],
            [false, '{"foo": "bar"]'],
            [false, '123', 'UTF-8'],
            [true, '{"fòô": "bàř"}', 'UTF-8'],
            [false, '{"fòô":"bàř",}', 'UTF-8'],
            [false, '{"fòô"}', 'UTF-8'],
            [false, '["fòô": "bàř"]', 'UTF-8'],
            [true, '["fòô"]', 'UTF-8'],
            [false, '{"fòô": "bàř"]', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isLowerCaseDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'foobar'],
            [false, 'foo bar'],
            [false, 'Foobar'],
            [true, 'fòôbàř', 'UTF-8'],
            [false, 'fòôbàř2', 'UTF-8'],
            [false, 'fòô bàř', 'UTF-8'],
            [false, 'fòôbÀŘ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isDataProvider(): array
    {
        return [
            [true, 'Gears\\String\\Str', 'Gears\\String\\Str'],
            [true, 'Gears\\String\\Str', 'Gears\\*\\Str'],
            [true, 'Gears\\String\\Str', 'Gears\\*\\*'],
            [true, 'Gears\\String\\Str', '*\\*\\*'],
            [true, 'Gears\\String\\Str', '*\\String\\*'],
            [true, 'Gears\\String\\Str', '*\\*\\Str'],
            [true, 'Gears\\String\\Str', '*\\Str'],
            [true, 'Gears\\String\\Str', '*'],
            [true, 'Gears\\String\\Str', '**'],
            [true, 'Gears\\String\\Str', '****'],
            [true, 'Gears\\String\\Str', '*Str'],
            [false, 'Gears\\String\\Str', '*\\'],
            [false, 'Gears\\String\\Str', 'Gears-*-*'],
        ];
    }

    /**
     * @return array
     */
    public function isSerializedDataProvider(): array
    {
        return [
            [false, ''],
            [true, 'a:1:{s:3:"foo";s:3:"bar";}'],
            [false, 'a:1:{s:3:"foo";s:3:"bar"}'],
            [true, \serialize(['foo' => 'bar'])],
            [true, 'a:1:{s:5:"fòô";s:5:"bàř";}', 'UTF-8'],
            [false, 'a:1:{s:5:"fòô";s:5:"bàř"}', 'UTF-8'],
            [true, \serialize(['fòô' => 'bár']), 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function isUpperCaseDataProvider(): array
    {
        return [
            [true, ''],
            [true, 'FOOBAR'],
            [false, 'FOO BAR'],
            [false, 'fOOBAR'],
            [true, 'FÒÔBÀŘ', 'UTF-8'],
            [false, 'FÒÔBÀŘ2', 'UTF-8'],
            [false, 'FÒÔ BÀŘ', 'UTF-8'],
            [false, 'FÒÔBàř', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function lastDataProvider(): array
    {
        return [
            ['', 'foo bar', -5],
            ['', 'foo bar', 0],
            ['r', 'foo bar', 1],
            ['bar', 'foo bar', 3],
            ['foo bar', 'foo bar', 7],
            ['foo bar', 'foo bar', 8],
            ['', 'fòô bàř', -5, 'UTF-8'],
            ['', 'fòô bàř', 0, 'UTF-8'],
            ['ř', 'fòô bàř', 1, 'UTF-8'],
            ['bàř', 'fòô bàř', 3, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 7, 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 8, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function lengthDataProvider(): array
    {
        return [
            [11, '  foo bar  '],
            [1, 'f'],
            [0, ''],
            [7, 'fòô bàř', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function linesDataProvider(): array
//    {
//        return [
//            [[], ''],
//            [[''], "\r\n"],
//            [['foo', 'bar'], "foo\nbar"],
//            [['foo', 'bar'], "foo\rbar"],
//            [['foo', 'bar'], "foo\r\nbar"],
//            [['foo', '', 'bar'], "foo\r\n\r\nbar"],
//            [['foo', 'bar', ''], "foo\r\nbar\r\n"],
//            [['', 'foo', 'bar'], "\r\nfoo\r\nbar"],
//            [['fòô', 'bàř'], "fòô\nbàř", 'UTF-8'],
//            [['fòô', 'bàř'], "fòô\rbàř", 'UTF-8'],
//            [['fòô', 'bàř'], "fòô\n\rbàř", 'UTF-8'],
//            [['fòô', 'bàř'], "fòô\r\nbàř", 'UTF-8'],
//            [['fòô', '', 'bàř'], "fòô\r\n\r\nbàř", 'UTF-8'],
//            [['fòô', 'bàř', ''], "fòô\r\nbàř\r\n", 'UTF-8'],
//            [['', 'fòô', 'bàř'], "\r\nfòô\r\nbàř", 'UTF-8'],
//            [['1111111111111111111'], '1111111111111111111', 'UTF-8'],
//            [['1111111111111111111111'], '1111111111111111111111', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function longestCommonPrefixDataProvider(): array
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['f', 'foo bar', 'far boo'],
            ['', 'toy car', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbar', 'fòô bar', 'UTF-8'],
            ['fòô bar', 'fòô bar', 'fòô bar', 'UTF-8'],
            ['fò', 'fòô bar', 'fòr bar', 'UTF-8'],
            ['', 'toy car', 'fòô bar', 'UTF-8'],
            ['', 'fòô bar', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function longestCommonSubstringDataProvider(): array
    {
        return [
            ['foo', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['oo ', 'foo bar', 'boo far'],
            ['foo ba', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['fòô', 'fòôbàř', 'fòô bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř', 'UTF-8'],
            [' bàř', 'fòô bàř', 'fòr bàř', 'UTF-8'],
            [' ', 'toy car', 'fòô bàř', 'UTF-8'],
            ['', 'fòô bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function longestCommonSuffixDataProvider(): array
    {
        return [
            ['bar', 'foobar', 'foo bar'],
            ['foo bar', 'foo bar', 'foo bar'],
            ['ar', 'foo bar', 'boo far'],
            ['', 'foo bad', 'foo bar'],
            ['', 'foo bar', ''],
            ['bàř', 'fòôbàř', 'fòô bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bàř', 'UTF-8'],
            [' bàř', 'fòô bàř', 'fòr bàř', 'UTF-8'],
            ['', 'toy car', 'fòô bàř', 'UTF-8'],
            ['', 'fòô bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function lowerCaseFirstDataProvider(): array
    {
        return [
            ['test', 'Test'],
            ['test', 'test'],
            ['1a', '1a'],
            ['σ test', 'Σ test', 'UTF-8'],
            [' Σ test', ' Σ test', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function offsetExistsDataProvider(): array
    {
        return [
            [true, 0],
            [true, 2],
            [false, 3],
            [true, -1],
            [true, -3],
            [false, -4],
        ];
    }

    /**
     * @return array
     */
    public function padBothDataProvider(): array
    {
        return [
            ['foo bar ', 'foo bar', 8],
            [' foo bar ', 'foo bar', 9, ' '],
            ['fòô bàř ', 'fòô bàř', 8, ' ', 'UTF-8'],
            [' fòô bàř ', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['fòô bàř¬', 'fòô bàř', 8, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬ø', 'UTF-8'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬øÿ', 'UTF-8'],
            ['¬øfòô bàř¬ø', 'fòô bàř', 11, '¬øÿ', 'UTF-8'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function padLeftDataProvider(): array
    {
        return [
            ['  foo bar', 'foo bar', 9],
            ['_*foo bar', 'foo bar', 9, '_*'],
            ['_*_foo bar', 'foo bar', 10, '_*'],
            ['  fòô bàř', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['¬øfòô bàř', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['¬ø¬øfòô bàř', 'fòô bàř', 11, '¬ø', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function padDataProvider(): array
    {
        return [
            // length <= str
            ['foo bar', 'foo bar', -1],
            ['foo bar', 'foo bar', 7],
            ['fòô bàř', 'fòô bàř', 7, ' ', 'right', 'UTF-8'],

            // right
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*', 'right'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø', 'right', 'UTF-8'],

            // left
            ['  foo bar', 'foo bar', 9, ' ', 'left'],
            ['_*foo bar', 'foo bar', 9, '_*', 'left'],
            ['¬ø¬fòô bàř', 'fòô bàř', 10, '¬ø', 'left', 'UTF-8'],

            // both
            ['foo bar ', 'foo bar', 8, ' ', 'both'],
            ['¬fòô bàř¬ø', 'fòô bàř', 10, '¬ø', 'both', 'UTF-8'],
            ['¬øfòô bàř¬øÿ', 'fòô bàř', 12, '¬øÿ', 'both', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function padRightDataProvider(): array
    {
        return [
            ['foo bar  ', 'foo bar', 9],
            ['foo bar_*', 'foo bar', 9, '_*'],
            ['foo bar_*_', 'foo bar', 10, '_*'],
            ['fòô bàř  ', 'fòô bàř', 9, ' ', 'UTF-8'],
            ['fòô bàř¬ø', 'fòô bàř', 9, '¬ø', 'UTF-8'],
            ['fòô bàř¬ø¬', 'fòô bàř', 10, '¬ø', 'UTF-8'],
            ['fòô bàř¬ø¬ø', 'fòô bàř', 11, '¬ø', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function prependDataProvider(): array
    {
        return [
            ['foobar', 'bar', 'foo'],
            ['fòôbàř', 'bàř', 'fòô', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function regexReplaceDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['bar', 'foo', 'f[o]+', 'bar'],
            ['//bar//', '/foo/', '/f[o]+/', '//bar//', 'msr', '#'],
            ['o bar', 'foo bar', 'f(o)o', '\1'],
            ['bar', 'foo bar', 'f[O]+\s', '', 'i'],
            ['foo', 'bar', '[[:alpha:]]{3}', 'foo'],
            ['', '', '', '', 'msr', '/', 'UTF-8'],
            ['bàř', 'fòô ', 'f[òô]+\s', 'bàř', 'msr', '/', 'UTF-8'],
            ['fòô', 'fò', '(ò)', '\\1ô', 'msr', '/', 'UTF-8'],
            ['fòô', 'bàř', '[[:alpha:]]{3}', 'fòô', 'msr', '/', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function removeHtmlBreakDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof <3', 'raboof <3', '<ä>'],
            ['řàbôòf <foo<lall>>>', 'řàbôòf<br/><foo<lall>>>', ' '],
            [
                'řàb <ô>òf\', ô<br><br/>foo <a href="#">lall</a>',
                'řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>',
                '<br><br/>',
            ],
            ['<∂∆ onerror="alert(xss)">˚åß', '<∂∆ onerror="alert(xss)">' . "\n" . '˚åß'],
            ['\'œ … \'’)', '\'œ … \'’)'],
        ];
    }

    /**
     * @return array
     */
    public function removeHtmlDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof ', 'raboof <3', '<3>'],
            ['řàbôòf>', 'řàbôòf<foo<lall>>>', '<lall><lall/>'],
            ['řàb òf\', ô<br/>foo lall', 'řàb <ô>òf\', ô<br/>foo <a href="#">lall</a>', '<br><br/>'],
            [' ˚åß', '<∂∆ onerror="alert(xss)"> ˚åß'],
            ['\'œ … \'’)', '\'œ … \'’)'],
        ];
    }

    /**
     * @return array
     */
    public function removeLeftDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['oo bar', 'foo bar', 'f'],
            ['bar', 'foo bar', 'foo '],
            ['foo bar', 'foo bar', 'oo'],
            ['foo bar', 'foo bar', 'oo bar'],
            ['oo bar', 'foo bar', S::create('foo bar')->first(1), 'UTF-8'],
            ['oo bar', 'foo bar', S::create('foo bar')->at(0), 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', 'UTF-8'],
            ['òô bàř', 'fòô bàř', 'f', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'òô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'òô bàř', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function removeRightDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', ''],
            ['foo ba', 'foo bar', 'r'],
            ['foo', 'foo bar', ' bar'],
            ['foo bar', 'foo bar', 'ba'],
            ['foo bar', 'foo bar', 'foo ba'],
            ['foo ba', 'foo bar', S::create('foo bar')->last(1), 'UTF-8'],
            ['foo ba', 'foo bar', S::create('foo bar')->at(6), 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', 'UTF-8'],
            ['fòô bà', 'fòô bàř', 'ř', 'UTF-8'],
            ['fòô', 'fòô bàř', ' bàř', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'bà', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', 'fòô bà', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function removeXssDataProvider(): array
    {
        return [
            ['', ''],
            [
                'Hello, i try to  your site',
                'Hello, i try to <script>alert(\'Hack\');</script> your site',
            ],
            [
                '<IMG >',
                '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>',
            ],
            ['<XSS >', '<XSS STYLE="behavior: url(xss.htc);">'],
            ['<∂∆ > ˚åß', '<∂∆ onerror="alert(xss)"> ˚åß'],
            ['\'œ … <a href="#foo"> \'’)', '\'œ … <a href="#foo"> \'’)'],
        ];
    }

    /**
     * @return array
     */
    public function emptyDataProvider(): array
    {
        return [
            [true, ''],
            [
                false,
                'Hello',
            ],
            [
                false,
                1,
            ],
            [
                false,
                1.1,
            ],
            [
                true,
                null,
            ],
        ];
    }

    /**
     * @return array
     */
    public function repeatDataProvider(): array
    {
        return [
            ['', 'foo', 0],
            ['foo', 'foo', 1],
            ['foofoo', 'foo', 2],
            ['foofoofoo', 'foo', 3],
            ['fòô', 'fòô', 1, 'UTF-8'],
            ['fòôfòô', 'fòô', 2, 'UTF-8'],
            ['fòôfòôfòô', 'fòô', 3, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function replaceAllDataProvider(): array
    {
        return [
            ['', '', [], ''],
            ['', '', [''], ''],
            ['foo', ' ', [' ', ''], 'foo'],
            ['foo', '\s', ['\s', '\t'], 'foo'],
            ['foo bar', 'foo bar', [''], ''],
            ['\1 bar', 'foo bar', ['f(o)o', 'foo'], '\1'],
            ['\1 \1', 'foo bar', ['foo', 'föö', 'bar'], '\1'],
            ['bar', 'foo bar', ['foo '], ''],
            ['far bar', 'foo bar', ['foo'], 'far'],
            ['bar bar', 'foo bar foo bar', ['foo ', ' foo'], ''],
            ['bar bar bar bar', 'foo bar foo bar', ['foo ', ' foo'], ['bar ', ' bar']],
            ['', '', [''], '', 'UTF-8'],
            ['fòô', ' ', [' ', '', '  '], 'fòô', 'UTF-8'],
            ['fòôòô', '\s', ['\s', 'f'], 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', [''], '', 'UTF-8'],
            ['bàř', 'fòô bàř', ['fòô '], '', 'UTF-8'],
            ['far bàř', 'fòô bàř', ['fòô'], 'far', 'UTF-8'],
            ['bàř bàř', 'fòô bàř fòô bàř', ['fòô ', 'fòô'], '', 'UTF-8'],
            ['bàř bàř', 'fòô bàř fòô bàř', ['fòô '], ''],
            ['bàř bàř', 'fòô bàř fòô bàř', ['fòô '], ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', ['Fòô '], ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', ['fòÔ '], ''],
            ['fòô bàř bàř', 'fòô bàř [[fòô]] bàř', ['[[fòô]] ', '[]'], ''],
            ['', '', [''], '', 'UTF-8', false],
            ['fòô', ' ', [' ', '', '  '], 'fòô', 'UTF-8', false],
            ['fòôòô', '\s', ['\s', 'f'], 'fòô', 'UTF-8', false],
            ['fòô bàř', 'fòô bàř', [''], '', 'UTF-8', false],
            ['bàř', 'fòô bàř', ['fòÔ '], '', 'UTF-8', false],
            ['bàř', 'fòô bàř', ['fòÔ '], [''], 'UTF-8', false],
            ['far bàř', 'fòô bàř', ['Fòô'], 'far', 'UTF-8', false],
        ];
    }

    /**
     * @return array
     */
    public function replaceBeginningDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', '', '', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar foo bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', '', '', 'fòô', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', '', 'UTF-8'],
            ['far bàř', 'fòô bàř', 'fòô', 'far', 'UTF-8'],
            ['bàř fòô bàř', 'fòô bàř fòô bàř', 'fòô ', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function replaceFirstDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foofoofoo', 'foofoo', 'foo', 'foofoo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar foo bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', '', 'UTF-8'],
            ['fòô bàř', 'fòô fòô bàř', 'fòô ', '', 'UTF-8'],
            ['far bàř', 'fòô bàř', 'fòô', 'far', 'UTF-8'],
            ['bàř fòô bàř', 'fòô bàř fòô bàř', 'fòô ', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function replaceLastDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foofoofoo', 'foofoo', 'foo', 'foofoo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['foo lall', 'foo bar', 'bar', 'lall'],
            ['foo bar foo ', 'foo bar foo bar', 'bar', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['fòô', 'fòô bàř', ' bàř', '', 'UTF-8'],
            ['fòôfar', 'fòô bàř', ' bàř', 'far', 'UTF-8'],
            ['fòô bàř fòô', 'fòô bàř fòô bàř', ' bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function replaceEndingDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', '', '', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['foo bar', 'foo bar', 'foo', '\1'],
            ['foo bar', 'foo bar', 'foo ', ''],
            ['foo lall', 'foo bar', 'bar', 'lall'],
            ['foo bar foo ', 'foo bar foo bar', 'bar', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', '', '', 'fòô', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['fòô', 'fòô bàř', ' bàř', '', 'UTF-8'],
            ['fòôfar', 'fòô bàř', ' bàř', 'far', 'UTF-8'],
            ['fòô bàř fòô', 'fòô bàř fòô bàř', ' bàř', '', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function replaceDataProvider(): array
    {
        return [
            ['', '', '', ''],
            ['foo', ' ', ' ', 'foo'],
            ['foo', '\s', '\s', 'foo'],
            ['foo bar', 'foo bar', '', ''],
            ['foo bar', 'foo bar', 'f(o)o', '\1'],
            ['\1 bar', 'foo bar', 'foo', '\1'],
            ['bar', 'foo bar', 'foo ', ''],
            ['far bar', 'foo bar', 'foo', 'far'],
            ['bar bar', 'foo bar foo bar', 'foo ', ''],
            ['', '', '', '', 'UTF-8'],
            ['fòô', ' ', ' ', 'fòô', 'UTF-8'],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8'],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8'],
            ['bàř', 'fòô bàř', 'fòô ', '', 'UTF-8'],
            ['far bàř', 'fòô bàř', 'fòô', 'far', 'UTF-8'],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', '', 'UTF-8'],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
            ['bàř bàř', 'fòô bàř fòô bàř', 'fòô ', ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', 'Fòô ', ''],
            ['fòô bàř fòô bàř', 'fòô bàř fòô bàř', 'fòÔ ', ''],
            ['fòô bàř bàř', 'fòô bàř [[fòô]] bàř', '[[fòô]] ', ''],
            ['', '', '', '', 'UTF-8', false],
            ['òô', ' ', ' ', 'òô', 'UTF-8', false],
            ['fòô', '\s', '\s', 'fòô', 'UTF-8', false],
            ['fòô bàř', 'fòô bàř', '', '', 'UTF-8', false],
            ['bàř', 'fòô bàř', 'Fòô ', '', 'UTF-8', false],
            ['far bàř', 'fòô bàř', 'fòÔ', 'far', 'UTF-8', false],
            ['bàř bàř', 'fòô bàř fòô bàř', 'Fòô ', '', 'UTF-8', false],
        ];
    }

    /**
     * @return array
     */
    public function reverseDataProvider(): array
    {
        return [
            ['', ''],
            ['raboof', 'foobar'],
            ['řàbôòf', 'fòôbàř', 'UTF-8'],
            ['řàb ôòf', 'fòô bàř', 'UTF-8'],
            ['∂∆ ˚åß', 'ßå˚ ∆∂', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function safeTruncateDataProvider(): array
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test', 'Testfoobar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['...', 'Test foo bar', 4, '...'],
            ['Test....', 'Test foo bar', 11, '....'],
            ['Test fòô bàř', 'Test fòô bàř', 12, '', 'UTF-8'],
            ['Test fòô', 'Test fòô bàř', 11, '', 'UTF-8'],
            ['Test fòô', 'Test fòô bàř', 8, '', 'UTF-8'],
            ['Test', 'Test fòô bàř', 7, '', 'UTF-8'],
            ['Test', 'Test fòô bàř', 4, '', 'UTF-8'],
            ['Test fòô bàř', 'Test fòô bàř', 12, 'ϰϰ', 'UTF-8'],
            ['Test fòôϰϰ', 'Test fòô bàř', 11, 'ϰϰ', 'UTF-8'],
            ['Testϰϰ', 'Test fòô bàř', 8, 'ϰϰ', 'UTF-8'],
            ['Testϰϰ', 'Test fòô bàř', 7, 'ϰϰ', 'UTF-8'],
            ['ϰϰ', 'Test fòô bàř', 4, 'ϰϰ', 'UTF-8'],
            ['What are your plans...', 'What are your plans today?', 22, '...'],
        ];
    }

    /**
     * @return array
     */
    public function shortenAfterWordDataProvider(): array
    {
        return [
            ['this...', 'this is a test', 5, '...'],
            ['this is...', 'this is öäü-foo test', 8, '...'],
            ['fòô', 'fòô bàř fòô', 6, ''],
            ['fòô bàř', 'fòô bàř fòô', 8, ''],
        ];
    }

    /**
     * @return array
     */
    public function shuffleDataProvider(): array
    {
        return [
            ['foo bar'],
            ['∂∆ ˚åß', 'UTF-8'],
            ['å´¥©¨ˆßå˚ ∆∂˙©å∑¥øœ¬', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function sliceDataProvider(): array
    {
        return [
            ['foobar', 'foobar', 0],
            ['foobar', 'foobar', 0, null],
            ['foobar', 'foobar', 0, 6],
            ['fooba', 'foobar', 0, 5],
            ['', 'foobar', 3, 0],
            ['', 'foobar', 3, 2],
            ['ba', 'foobar', 3, 5],
            ['ba', 'foobar', 3, -1],
            ['fòôbàř', 'fòôbàř', 0, null, 'UTF-8'],
            ['fòôbàř', 'fòôbàř', 0, null],
            ['fòôbàř', 'fòôbàř', 0, 6, 'UTF-8'],
            ['fòôbà', 'fòôbàř', 0, 5, 'UTF-8'],
            ['', 'fòôbàř', 3, 0, 'UTF-8'],
            ['', 'fòôbàř', 3, 2, 'UTF-8'],
            ['bà', 'fòôbàř', 3, 5, 'UTF-8'],
            ['bà', 'fòôbàř', 3, -1, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function slugifyDataProvider(): array
    {
        return [
            ['foo-bar', ' foo  bar '],
            ['foo-bar', 'foo -.-"-...bar'],
            ['another-und-foo-bar', 'another..& foo -.-"-...bar'],
            ['foo-dbar', " Foo d'Bar "],
            ['a-string-with-dashes', 'A string-with-dashes'],
            ['using-strings-like-foo-bar', 'Using strings like fòô bàř'],
            ['numbers-1234', 'numbers 1234'],
            ['perevirka-ryadka', 'перевірка рядка'],
            ['bukvar-s-bukvoi-y', 'букварь с буквой ы'],
            ['podehal-k-podezdu-moego-doma', 'подъехал к подъезду моего дома'],
            ['foo:bar:baz', 'Foo bar baz', ':'],
            ['a_string_with_underscores', 'A_string with_underscores', '_'],
            ['a_string_with_dashes', 'A string-with-dashes', '_'],
            ['a\string\with\dashes', 'A string-with-dashes', '\\'],
            ['an_odd_string', '--   An odd__   string-_', '_'],
        ];
    }

    /**
     * @return array
     */
    public function snakeizeDataProvider(): array
    {
        return [
            ['snake_case', 'SnakeCase'],
            ['snake_case', 'Snake-Case'],
            ['snake_case', 'snake case'],
            ['snake_case', 'snake -case'],
            ['snake_case', 'snake - case'],
            ['snake_case', 'snake_case'],
            ['camel_c_test', 'camel c test'],
            ['string_with_1_number', 'string_with 1 number'],
            ['string_with_1_number', 'string_with1number'],
            ['string_with_2_2_numbers', 'string-with-2-2 numbers'],
            ['data_rate', 'data_rate'],
            ['background_color', 'background-color'],
            ['yes_we_can', 'yes_we_can'],
            ['moz_something', '-moz-something'],
            ['car_speed', '_car_speed_'],
            ['serve_h_t_t_p', 'ServeHTTP'],
            ['1_camel_2_case', '1camel2case'],
            ['camel_σase', 'camel σase', 'UTF-8'],
            ['στανιλ_case', 'Στανιλ case', 'UTF-8'],
            ['σamel_case', 'σamel  Case', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function splitDataProvider(): array
//    {
//        return [
//            [['foo,bar,baz'], 'foo,bar,baz', ''],
//            [['foo,bar,baz'], 'foo,bar,baz', '-'],
//            [['foo', 'bar', 'baz'], 'foo,bar,baz', ','],
//            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', -1],
//            [[], 'foo,bar,baz', ',', 0],
//            [['foo'], 'foo,bar,baz', ',', 1],
//            [['foo', 'bar'], 'foo,bar,baz', ',', 2],
//            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', 3],
//            [['foo', 'bar', 'baz'], 'foo,bar,baz', ',', 10],
//            [['fòô,bàř,baz'], 'fòô,bàř,baz', '-', -1, 'UTF-8'],
//            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', -1, 'UTF-8'],
//            [[], 'fòô,bàř,baz', ',', 0, 'UTF-8'],
//            [['fòô'], 'fòô,bàř,baz', ',', 1, 'UTF-8'],
//            [['fòô', 'bàř'], 'fòô,bàř,baz', ',', 2, 'UTF-8'],
//            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', 3, 'UTF-8'],
//            [['fòô', 'bàř', 'baz'], 'fòô,bàř,baz', ',', 10, 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function startsWithDataProvider(): array
    {
        return [
            [true, 'foo bars', 'foo bar'],
            [true, 'FOO bars', 'foo bar', false],
            [true, 'FOO bars', 'foo BAR', false],
            [true, 'FÒÔ bàřs', 'fòô bàř', false, 'UTF-8'],
            [true, 'fòô bàřs', 'fòô BÀŘ', false, 'UTF-8'],
            [false, 'foo bar', 'bar'],
            [false, 'foo bar', 'foo bars'],
            [false, 'FOO bar', 'foo bars'],
            [false, 'FOO bars', 'foo BAR'],
            [false, 'FÒÔ bàřs', 'fòô bàř', true, 'UTF-8'],
            [false, 'fòô bàřs', 'fòô BÀŘ', true, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function startsWithAnyDataProvider(): array
    {
        return [
            [true, 'foo bars', ['foo bar']],
            [true, 'foo bars', ['foo', 'bar']],
            [true, 'FOO bars', ['foo', 'bar'], false],
            [true, 'FOO bars', ['foo', 'BAR'], false],
            [true, 'FÒÔ bàřs', ['fòô', 'bàř'], false, 'UTF-8'],
            [true, 'fòô bàřs', ['fòô BÀŘ'], false, 'UTF-8'],
            [false, 'foo bar', ['bar']],
            [false, 'foo bar', ['foo bars']],
            [false, 'FOO bar', ['foo bars']],
            [false, 'FOO bars', ['foo BAR']],
            [false, 'FÒÔ bàřs', ['fòô bàř'], true, 'UTF-8'],
            [false, 'fòô bàřs', ['fòô BÀŘ'], true, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function stripWhitespaceDataProvider(): array
    {
        return [
            ['foobar', '  foo   bar  '],
            ['teststring', 'test string'],
            ['Οσυγγραφέας', '   Ο     συγγραφέας  '],
            ['123', ' 123 '],
            ['', ' ', 'UTF-8'], // no-break space (U+00A0)
            ['', '           ', 'UTF-8'], // spaces U+2000 to U+200A
            ['', ' ', 'UTF-8'], // narrow no-break space (U+202F)
            ['', ' ', 'UTF-8'], // medium mathematical space (U+205F)
            ['', '　', 'UTF-8'], // ideographic space (U+3000)
            ['123', '  1  2  3　　', 'UTF-8'],
            ['', ' '],
            ['', ''],
        ];
    }

    /**
     * @return array
     */
    public function substrDataProvider(): array
    {
        return [
            ['foo bar', 'foo bar', 0],
            ['bar', 'foo bar', 4],
            ['bar', 'foo bar', 4, null],
            ['o b', 'foo bar', 2, 3],
            ['', 'foo bar', 4, 0],
            ['fòô bàř', 'fòô bàř', 0, null, 'UTF-8'],
            ['bàř', 'fòô bàř', 4, null, 'UTF-8'],
            ['ô b', 'fòô bàř', 2, 3, 'UTF-8'],
            ['', 'fòô bàř', 4, 0, 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function surroundDataProvider(): array
    {
        return [
            ['__foobar__', 'foobar', '__'],
            ['test', 'test', ''],
            ['**', '', '*'],
            ['¬fòô bàř¬', 'fòô bàř', '¬'],
            ['ßå∆˚ test ßå∆˚', ' test ', 'ßå∆˚'],
        ];
    }

    /**
     * @return array
     */
    public function swapCaseDataProvider(): array
    {
        return [
            ['TESTcASE', 'testCase'],
            ['tEST-cASE', 'Test-Case'],
            [' - σASH  cASE', ' - Σash  Case', 'UTF-8'],
            ['νΤΑΝΙΛ', 'Ντανιλ', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function appendDataProvider(): array
//    {
//        return [
//            ['foobar', 'foo', 'bar'],
//            ['fòôbàř', 'fòô', 'bàř', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function tidyDataProvider(): array
    {
        return [
            ['"I see..."', '“I see…”'],
            ["'This too'", '‘This too’'],
            ['test-dash', 'test—dash'],
            ['Ο συγγραφέας είπε...', 'Ο συγγραφέας είπε…'],
        ];
    }

    /**
     * @return array
     */
    public function titleizeDataProvider(): array
    {
        $ignore = ['at', 'by', 'for', 'in', 'of', 'on', 'out', 'to', 'the'];

        return [
            ['Title Case', 'TITLE CASE'],
            ['Testing The Method', 'testing the method'],
            ['Testing the Method', 'testing the method', $ignore],
            ['I Like to Watch Dvds at Home', 'i like to watch DVDs at home', $ignore],
            ['Θα Ήθελα Να Φύγει', '  Θα ήθελα να φύγει  '],
        ];
    }

    /**
     * @return array
     */
    public function toTransliterateDataProvider(): array
    {
        return [
            ['foo bar', 'fòô bàř'],
            [' TEST ', ' ŤÉŚŢ '],
            ['ph = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['lysaia gora', 'лысая гора'],
            ['shchuka', 'щука'],
            ['Han Zi ', '漢字'],
            ['xin chao the gioi', 'xin chào thế giới'],
            ['XIN CHAO THE GIOI', 'XIN CHÀO THẾ GIỚI'],
            ['dam phat chet luon', 'đấm phát chết luôn'],
            [' ', ' '], // no-break space (U+00A0)
            ['           ', '           '], // spaces U+2000 to U+200A
            [' ', ' '], // narrow no-break space (U+202F)
            [' ', ' '], // medium mathematical space (U+205F)
            [' ', '　'], // ideographic space (U+3000)
            ['?', '𐍉'], // some uncommon, unsupported character (U+10349)
        ];
    }

    /**
     * @return array
     */
    public function toBooleanDataProvider(): array
    {
        return [
            [true, 'true'],
            [true, '1'],
            [true, 'on'],
            [true, 'ON'],
            [true, 'yes'],
            [true, '999'],
            [false, 'false'],
            [false, '0'],
            [false, 'off'],
            [false, 'OFF'],
            [false, 'no'],
            [false, '-999'],
            [false, ''],
            [false, ' '],
            [false, '  ', 'UTF-8'], // narrow no-break space (U+202F)
        ];
    }

    /**
     * @return array
     */
//    public function toLowerCaseDataProvider(): array
//    {
//        return [
//            ['foo bar', 'FOO BAR'],
//            [' foo_bar ', ' FOO_bar '],
//            ['fòô bàř', 'FÒÔ BÀŘ', 'UTF-8'],
//            [' fòô_bàř ', ' FÒÔ_bàř ', 'UTF-8'],
//            ['αυτοκίνητο', 'ΑΥΤΟΚΊΝΗΤΟ', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function toSpacesDataProvider(): array
    {
        return [
            ['    foo    bar    ', '	foo	bar	'],
            ['     foo     bar     ', '	foo	bar	', 5],
            ['    foo  bar  ', '		foo	bar	', 2],
            ['foobar', '	foo	bar	', 0],
            ["    foo\n    bar", "	foo\n	bar"],
            ["    fòô\n    bàř", "	fòô\n	bàř"],
        ];
    }

    /**
     * @return array
     */
//    public function toStringDataProvider(): array
//    {
//        return [
//            ['', null],
//            ['', false],
//            ['1', true],
//            ['-9', -9],
//            ['1.18', 1.18],
//            [' string  ', ' string  '],
//        ];
//    }

    /**
     * @return array
     */
    public function toTabsDataProvider(): array
    {
        return [
            ['	foo	bar	', '    foo    bar    '],
            ['	foo	bar	', '     foo     bar     ', 5],
            ['		foo	bar	', '    foo  bar  ', 2],
            ["	foo\n	bar", "    foo\n    bar"],
            ["	fòô\n	bàř", "    fòô\n    bàř"],
        ];
    }

    /**
     * @return array
     */
//    public function toTitleCaseDataProvider(): array
//    {
//        return [
//            ['Foo Bar', 'foo bar'],
//            [' Foo_Bar ', ' foo_bar '],
//            ['Fòô Bàř', 'fòô bàř', 'UTF-8'],
//            [' Fòô_Bàř ', ' fòô_bàř ', 'UTF-8'],
//            ['Αυτοκίνητο Αυτοκίνητο', 'αυτοκίνητο αυτοκίνητο', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
//    public function toUpperCaseDataProvider(): array
//    {
//        return [
//            ['FOO BAR', 'foo bar'],
//            [' FOO_BAR ', ' FOO_bar '],
//            ['FÒÔ BÀŘ', 'fòô bàř', 'UTF-8'],
//            [' FÒÔ_BÀŘ ', ' FÒÔ_bàř ', 'UTF-8'],
//            ['ΑΥΤΟΚΊΝΗΤΟ', 'αυτοκίνητο', 'UTF-8'],
//            ['ἙΛΛΗΝΙΚῊ', 'ἑλληνικὴ'],
//        ];
//    }

    /**
     * @return array
     */
    public function trimLeftDataProvider(): array
    {
        return [
            ['foo   bar  ', '  foo   bar  '],
            ['foo bar', ' foo bar'],
            ['foo bar ', 'foo bar '],
            ["foo bar \n\t", "\n\t foo bar \n\t"],
            ['fòô   bàř  ', '  fòô   bàř  '],
            ['fòô bàř', ' fòô bàř'],
            ['fòô bàř ', 'fòô bàř '],
            ['foo bar', '--foo bar', '-'],
            ['fòô bàř', 'òòfòô bàř', 'ò', 'UTF-8'],
            ["fòô bàř \n\t", "\n\t fòô bàř \n\t", null, 'UTF-8'],
            ['fòô ', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
            ['fòô  ', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
            ['fòô', '           fòô', null, 'UTF-8'], // spaces U+2000 to U+200A
        ];
    }

    /**
     * @return array
     */
//    public function trimDataProvider(): array
//    {
//        return [
//            ['foo   bar', '  foo   bar  '],
//            ['foo bar', ' foo bar'],
//            ['foo bar', 'foo bar '],
//            ['foo bar', "\n\t foo bar \n\t"],
//            ['fòô   bàř', '  fòô   bàř  '],
//            ['fòô bàř', ' fòô bàř'],
//            ['fòô bàř', 'fòô bàř '],
//            [' foo bar ', "\n\t foo bar \n\t", "\n\t"],
//            ['fòô bàř', "\n\t fòô bàř \n\t", null, 'UTF-8'],
//            ['fòô', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
//            ['fòô', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
//            ['fòô', '           fòô', null, 'UTF-8'], // spaces U+2000 to U+200A
//        ];
//    }

    /**
     * @return array
     */
    public function trimRightDataProvider(): array
    {
        return [
            ['  foo   bar', '  foo   bar  '],
            ['foo bar', 'foo bar '],
            [' foo bar', ' foo bar'],
            ["\n\t foo bar", "\n\t foo bar \n\t"],
            ['  fòô   bàř', '  fòô   bàř  '],
            ['fòô bàř', 'fòô bàř '],
            [' fòô bàř', ' fòô bàř'],
            ['foo bar', 'foo bar--', '-'],
            ['fòô bàř', 'fòô bàřòò', 'ò', 'UTF-8'],
            ["\n\t fòô bàř", "\n\t fòô bàř \n\t", null, 'UTF-8'],
            [' fòô', ' fòô ', null, 'UTF-8'], // narrow no-break space (U+202F)
            ['  fòô', '  fòô  ', null, 'UTF-8'], // medium mathematical space (U+205F)
            ['fòô', 'fòô           ', null, 'UTF-8'], // spaces U+2000 to U+200A
        ];
    }

    /**
     * @return array
     */
    public function truncateDataProvider(): array
    {
        return [
            ['Test foo bar', 'Test foo bar', 12],
            ['Test foo ba', 'Test foo bar', 11],
            ['Test foo', 'Test foo bar', 8],
            ['Test fo', 'Test foo bar', 7],
            ['Test', 'Test foo bar', 4],
            ['Test foo bar', 'Test foo bar', 12, '...'],
            ['Test foo...', 'Test foo bar', 11, '...'],
            ['Test ...', 'Test foo bar', 8, '...'],
            ['Test...', 'Test foo bar', 7, '...'],
            ['T...', 'Test foo bar', 4, '...'],
            ['Test fo....', 'Test foo bar', 11, '....'],
            ['Test fòô bàř', 'Test fòô bàř', 12, '', 'UTF-8'],
            ['Test fòô bà', 'Test fòô bàř', 11, '', 'UTF-8'],
            ['Test fòô', 'Test fòô bàř', 8, '', 'UTF-8'],
            ['Test fò', 'Test fòô bàř', 7, '', 'UTF-8'],
            ['Test', 'Test fòô bàř', 4, '', 'UTF-8'],
            ['Test fòô bàř', 'Test fòô bàř', 12, 'ϰϰ', 'UTF-8'],
            ['Test fòô ϰϰ', 'Test fòô bàř', 11, 'ϰϰ', 'UTF-8'],
            ['Test fϰϰ', 'Test fòô bàř', 8, 'ϰϰ', 'UTF-8'],
            ['Test ϰϰ', 'Test fòô bàř', 7, 'ϰϰ', 'UTF-8'],
            ['Teϰϰ', 'Test fòô bàř', 4, 'ϰϰ', 'UTF-8'],
            ['What are your pl...', 'What are your plans today?', 19, '...'],
        ];
    }

    /**
     * @return array
     */
    public function underscoredDataProvider(): array
    {
        return [
            ['test_case', 'testCase'],
            ['test_case', 'Test-Case'],
            ['test_case', 'test case'],
            ['test_case', 'test -case'],
            ['_test_case', '-test - case'],
            ['test_case', 'test_case'],
            ['test_c_test', '  test c test'],
            ['test_u_case', 'TestUCase'],
            ['test_c_c_test', 'TestCCTest'],
            ['string_with1number', 'string_with1number'],
            ['string_with_2_2_numbers', 'String-with_2_2 numbers'],
            ['1test2case', '1test2case'],
            ['yes_we_can', 'yesWeCan'],
            ['test_σase', 'test Σase', 'UTF-8'],
            ['στανιλ_case', 'Στανιλ case', 'UTF-8'],
            ['σash_case', 'Σash  Case', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
    public function upperCamelizeDataProvider(): array
    {
        return [
            ['CamelCase', 'camelCase'],
            ['CamelCase', 'Camel-Case'],
            ['CamelCase', 'camel case'],
            ['CamelCase', 'camel -case'],
            ['CamelCase', 'camel - case'],
            ['CamelCase', 'camel_case'],
            ['CamelCTest', 'camel c test'],
            ['StringWith1Number', 'string_with1number'],
            ['StringWith22Numbers', 'string-with-2-2 numbers'],
            ['1Camel2Case', '1camel2case'],
            ['CamelΣase', 'camel σase', 'UTF-8'],
            ['ΣτανιλCase', 'στανιλ case', 'UTF-8'],
            ['ΣamelCase', 'Σamel  Case', 'UTF-8'],
        ];
    }

    /**
     * @return array
     */
//    public function upperCaseFirstDataProvider(): array
//    {
//        return [
//            ['Test', 'Test'],
//            ['Test', 'test'],
//            ['1a', '1a'],
//            ['Σ test', 'σ test', 'UTF-8'],
//            [' σ test', ' σ test', 'UTF-8'],
//        ];
//    }

    /**
     * @return array
     */
    public function strBeginsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = \base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, '0123こ', true, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '0123', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'UTF-8', 'EUC-JP'],
            [$string_ascii, 'a', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'A', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'b', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, '', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'abc', true, 'UTF-8', null],
            [$string_ascii, 'bc', false, 'UTF-8', null],
            [$string_ascii, '', true, 'UTF-8', null],
            [$string_mb, \base64_decode('5pel5pys6Kqe', true), true, 'UTF-8', null],
            [$string_mb, \base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, 'UTF-8', null],
            [$string_mb, '', true, 'UTF-8', null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ΤῊ', false, 'UTF-8', null],
        ];
    }

    /**
     * @return array
     */
    public function strEndsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = \base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, 'い。', true, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'UTF-8', 'EUC-JP'],
            [$euc_jp, 'い。', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'UTF-8', 'EUC-JP'],
            [$string_ascii, 'f', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'F', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'e', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, '', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'def', true, 'UTF-8', null],
            [$string_ascii, 'de', false, 'UTF-8', null],
            [$string_ascii, '', true, 'UTF-8', null],
            [$string_mb, \base64_decode('77yZ44CC', true), true, 'UTF-8', null],
            [$string_mb, \base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, 'UTF-8', null],
            [$string_mb, '', true, 'UTF-8', null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ἙΛΛΗΝΙΚῊ', false, 'UTF-8', null],
        ];
    }

    /**
     * @return array
     */
    public function strIbeginsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = \base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, '0123こ', true, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '0123', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'UTF-8', 'EUC-JP'],
            [$string_ascii, 'a', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'A', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'b', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, '', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'abc', true, 'UTF-8', null],
            [$string_ascii, 'AbC', true, 'UTF-8', null],
            [$string_ascii, 'bc', false, 'UTF-8', null],
            [$string_ascii, '', true, 'UTF-8', null],
            [$string_mb, \base64_decode('5pel5pys6Kqe', true), true, 'UTF-8', null],
            [$string_mb, \base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, 'UTF-8', null],
            [$string_mb, '', true, 'UTF-8', null],
            ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ΤῊ', true, 'UTF-8', null],
        ];
    }

    /**
     * @return array
     */
    public function strIendsDataProvider(): array
    {
        $euc_jp = '0123この文字列は日本語です。EUC-JPを使っています。0123日本語は面倒臭い。';
        $string_ascii = 'abc def';
        $string_mb = \base64_decode('5pel5pys6Kqe44OG44Kt44K544OI44Gn44GZ44CCMDEyMzTvvJXvvJbvvJfvvJjvvJnjgII=', true);

        return [
            [$euc_jp, 'い。', true, 'UTF-8', 'EUC-JP'],
            [$euc_jp, '韓国語', false, 'UTF-8', 'EUC-JP'],
            [$euc_jp, 'い。', true, 'EUC-JP', null],
            [$euc_jp, '韓国語', false, 'EUC-JP', null],
            [$euc_jp, '', true, 'UTF-8', 'EUC-JP'],
            [$string_ascii, 'f', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'F', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'e', false, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, '', true, 'UTF-8', 'ISO-8859-1'],
            [$string_ascii, 'def', true, 'UTF-8', null],
            [$string_ascii, 'DeF', true, 'UTF-8', null],
            [$string_ascii, 'de', false, 'UTF-8', null],
            [$string_ascii, '', true, 'UTF-8', null],
            [$string_mb, \base64_decode('77yZ44CC', true), true, 'UTF-8', null],
            [$string_mb, \base64_decode('44GT44KT44Gr44Gh44Gv44CB5LiW55WM', true), false, 'UTF-8', null],
            [$string_mb, '', true, 'UTF-8', null],
            // ['Τὴ γλῶσσα μοῦ ἔδωσαν ἑλληνικὴ', 'ἙΛΛΗΝΙΚῊ', true, 'UTF-8', null], // php 7.3 thingy
        ];
    }
}
