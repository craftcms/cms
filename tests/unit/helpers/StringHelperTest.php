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
     * @param $result
     * @param $haystack
     * @param $needle
     */
    public function testEndsWith($result, $haystack, $needle)
    {
        $endsWith = StringHelper::endsWith($haystack, $needle);
        $this->assertSame($result, $endsWith);
    }

    /**
     * @dataProvider camelCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCamelCase($result, $input)
    {
        $toCamel = StringHelper::camelCase($input);
        $this->assertSame($result, $toCamel);
    }

    /**
     * @dataProvider containsAllDataProvider
     *
     * @param      $result
     * @param      $haystack
     * @param      $needle
     * @param bool $caseSensitive
     */
    public function testContainsAll($result, $haystack, $needle, $caseSensitive = true)
    {
        $containsAll = StringHelper::containsAll($haystack, $needle, $caseSensitive);
        $this->assertSame($result, $containsAll);
    }

    /**
     *
     */
    public function testContainsAllExceptions()
    {
        // Test that empty array with a string in it returns an exception.
        $this->tester->expectThrowable(ErrorException::class, function() {
            StringHelper::containsAll('', ['']);
        });
    }

    /**
     * @dataProvider uppercaseFirstDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testUppercaseFirst($result, $input)
    {
        $uppercaseFirst = StringHelper::upperCaseFirst($input);
        $this->assertSame($result, $uppercaseFirst);
    }

    /**
     * @dataProvider indexOfDataProvider
     *
     * @param $result
     * @param $haystack
     * @param $needle
     */
    public function testIndexOf($result, $haystack, $needle)
    {
        $index = StringHelper::indexOf($haystack, $needle);
        $this->assertSame($result, $index);
    }

    /**
     *
     */
    public function testStringIndexException()
    {
        $this->tester->expectThrowable(ErrorException::class, function() {
            StringHelper::indexOf('', '');
        });
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
     * @param $result
     * @param $input
     */
    public function testToSnakeCase($result, $input)
    {
        $toSnake = StringHelper::toSnakeCase($input);
        $this->assertSame($result, $toSnake);
    }

    /**
     * @dataProvider mb4DataProvider
     *
     * @param $result
     * @param $input
     */
    public function testIsMb4($result, $input)
    {
        $isMb4 = StringHelper::containsMb4($input);
        $this->assertSame($result, $isMb4);
    }

    /**
     * @dataProvider charsAsArrayDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCharsAsArray($result, $input)
    {
        $charsArray = StringHelper::charsAsArray($input);
        $this->assertSame($result, $charsArray);
    }

    /**
     * @dataProvider toAsciiDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToAscii($result, $input)
    {
        $toAscii = StringHelper::toAscii($input);
        $this->assertSame($result, $toAscii);
    }

    /**
     * @dataProvider firstDataProvider
     *
     * @param $result
     * @param $input
     * @param $requiredChars
     */
    public function testFirst($result, $input, $requiredChars)
    {
        $stripped = StringHelper::first($input, $requiredChars);
        $this->assertSame($result, $stripped);
    }

    /**
     * @dataProvider stripHtmlDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testHtmlStripping($result, $input)
    {
        $stripped = StringHelper::stripHtml($input);
        $this->assertSame($result, $stripped);
    }

    /**
     * @dataProvider uuidDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testIsUUID($result, $input)
    {
        $isUUID = StringHelper::isUUID($input);
        $this->assertSame($result, $isUUID);
    }

    /**
     * @dataProvider collapseWhitespaceDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testWhitespaceCollapse($result, $input)
    {
        $whitespaceGone = StringHelper::collapseWhitespace($input);
        $this->assertSame($result, $whitespaceGone);
    }

    /**
     * @dataProvider whitespaceDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testIsWhitespace($result, $input)
    {
        $isWhitespace = StringHelper::isWhitespace($input);
        $this->assertSame($result, $isWhitespace);
        $this->assertIsBool($isWhitespace);
    }

    /**
     * @dataProvider splitDataProvider
     *
     * @param        $result
     * @param        $input
     * @param string $splitter
     */
    public function testStringSplit($result, $input, $splitter = ',')
    {
        $splitString = StringHelper::split($input, $splitter);
        $this->assertSame($result, $splitString);
    }

    /**
     * @dataProvider delimitDataProvider
     *
     * @param $result
     * @param $input
     * @param $delimited
     */
    public function testDelimit($result, $input, $delimited)
    {
        $delimitedString = StringHelper::delimit($input, $delimited);
        $this->assertSame($result, $delimitedString);
        $this->assertIsString($delimitedString);
    }

    /**
     * @dataProvider ensureRightDataProvider
     *
     * @param $result
     * @param $input
     * @param $ensure
     */
    public function testEnsureRight($result, $input, $ensure)
    {
        $this->assertSame($result, StringHelper::ensureRight($input, $ensure));
    }

    /**
     * @dataProvider randomStringWithCharsDataProvider
     *
     * @param $valid
     * @param int $length
     */
    public function testRandomStringWithChars($valid, int $length)
    {
        $str = StringHelper::randomStringWithChars($valid, $length);
        $strLen = mb_strlen($str);

        $this->assertSame($length, $strLen);

        // Loop through the string and see if any of the characters arent on the list of allowed chars.
        for ($i = 0; $i < $strLen; $i++) {
            if (mb_strpos($valid, $str[$i]) === false) {
                $this->fail('Invalid chars');
            }
        }
    }

    /**
     * @dataProvider mb4EncodingDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testMb4Encoding($result, $input)
    {
        $mb4String = StringHelper::encodeMb4($input);
        $this->assertSame($result, $mb4String);
        $this->assertIsString($mb4String);

        $this->assertFalse(StringHelper::containsMb4($mb4String));
    }

    /**
     * @dataProvider convertToUtf8DataProvider
     *
     * @param $result
     * @param $input
     */
    public function testConvertToUtf8($result, $input)
    {
        $utf8 = StringHelper::convertToUtf8($input);
        $this->assertSame($result, $utf8);
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
        $deArray = ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'];
        $this->assertArrayNotHasKey('de', StringHelper::asciiCharMap(false, 'de'));
        $deMap = StringHelper::asciiCharMap(true, 'de');

        foreach ($deArray as $deChar) {
            $this->assertArrayHasKey($deChar, $deMap);
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
     * @param $result
     * @param $input
     * @param $glue
     */
    public function testToString($result, $input, $glue = ',')
    {
        $string = StringHelper::toString($input, $glue);
        $this->assertSame($result, $string);
    }

    /**
     * @dataProvider randomStringDataProvider
     *
     * @param $length
     * @param $extendedChars
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
     * @param $result
     * @param $input
     */
    public function testToPascalCase($result, $input)
    {
        $pascal = StringHelper::toPascalCase($input);
        $this->assertSame($result, $pascal);
    }

    /**
     * @dataProvider toCamelCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToCamelCase($result, $input)
    {
        $camel = StringHelper::toCamelCase($input);
        $this->assertSame($result, $camel);
    }

    /**
     * @dataProvider toKebabCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToKebabCase($result, $input)
    {
        $kebab = StringHelper::toKebabCase($input);
        $this->assertSame($result, $kebab);
    }

    /**
     * @dataProvider linesDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testLines($result, $input)
    {
        $lines = StringHelper::lines($input);
        $this->assertCount($result, $lines);
    }

    /**
     * @dataProvider toUppercaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToUppercase($result, $input)
    {
        $uppercase = StringHelper::toUpperCase($input);
        $this->assertSame($result, $uppercase);
    }

    /**
     * @dataProvider trimDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testTrim($result, $input)
    {
        $trim = StringHelper::trim($input);
        $this->assertSame($result, $trim);
    }

    /**
     * @dataProvider toTitleCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToTitleCase($result, $input)
    {
        $toTitleCase = StringHelper::toTitleCase($input);
        $this->assertSame($result, $toTitleCase);
    }

    /**
     * @dataProvider toLowerCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testToLowerCase($result, $input)
    {
        $toLower = StringHelper::toLowerCase($input);
        $this->assertSame($result, $toLower);
    }

    /**
     * @dataProvider titelizeDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testTitleize($result, $input)
    {
        $titelize = StringHelper::titleize($input);
        $this->assertSame($result, $titelize);
    }

    /**
     * @dataProvider swapCaseDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testSwapCase($result, $input)
    {
        $swap = StringHelper::swapCase($input);
        $this->assertSame($result, $swap);
    }

    /**
     * @dataProvider substrDataProvider
     *
     * @param      $result
     * @param      $input
     * @param      $start
     * @param null $length
     */
    public function testSubstr($result, $input, $start, $length = null)
    {
        $substr = StringHelper::substr($input, $start, $length);
        $this->assertSame($result, $substr);
    }

    // Data Properties
    // =========================================================================

    /**
     * @return array
     */
    public function substrDataProvider(): array
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
    public function swapCaseDataProvider(): array
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
    public function titelizeDataProvider(): array
    {
        return [
            ['Test S 2 S Zw 2', 'test s 2 s zw 2'],
            ['😂 😁', '😂 😁'],
            ['Test Test Craft Cms !@#$%^&  *(abc)', 'Test test CRAFT cms !@#$%^&  *(abc)'],
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
        ];
    }

    /**
     * @return array
     */
    public function containsAllDataProvider(): array
    {
        return [
            [true, 'haystack', ['haystack']],
            [true, 'some haystackedy stack', ['stackedy']],
            [true, ' ', [' ']],
            [true, 'iam some text', ['tEXt'], false],
            [false, 'iam some text', ['tEXt']],
            [false, '', []],
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
            ['craft_cms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22_alpha_n_numeric', '22 AlphaN Numeric'],

        ];
    }

    /**
     * @return array
     */
    public function ensureRightDataProvider(): array
    {
        return [
            ['hello', 'hello', 'o'],
            ['!@#$%^&*()1234567890', '!@#$%^&*()1234567890', '567890'],
            ['hello, my name is', 'hello, my name is', 'e is'],
            ['hEllo, my name is', 'hEllo, my name is', 'Ello, my name is'],
            ['😀😁😂', '😀😁😂', '😂'],

            // Assert that without matches it gets added to the end.
            ['hEllo, my name ishello, m', 'hEllo, my name is', 'hello, m'],
            ['😀😁1aA!😂😁1aA!', '😀😁1aA!😂', '😁1aA!'],
            ['!@#$%^&*()1234567 890567890', '!@#$%^&*()1234567 890', '567890'],

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
            ['', '😂 😁'],
            ['testTestCraftCmsAbc', 'Test test CRAFT cms !@#$%^&  *(abc)'],
            ['craftCms', 'Craft Cms'],
            ['craftCms', 'CRAFT CMS'],
            ['craftcms', 'CRAFTCMS'],
            ['', ''],
            ['', '😘'],
            ['22AlphaNNumeric', '22 AlphaN Numeric'],
            ['', '!@#$%  ^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function toKebabCaseDataProvider(): array
    {
        return [
            ['test-s-2-s-zw-2', 'test s 2 s zw 2'],
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
}
