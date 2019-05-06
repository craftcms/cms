<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\StringHelper;
use craft\helpers\Stringy;
use craft\test\mockclasses\ToStringTest;
use function mb_strlen;
use function mb_strpos;
use stdClass;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Unit tests for the String Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class StringHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testUtf8Definition()
    {
        $this->assertSame('UTF-8', StringHelper::UTF8);
    }

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
     * @dataProvider endsWithData
     * @param $result
     * @param $haystack
     * @param $needle
     */
    public function testEndsWith($result, $haystack, $needle)
    {
        $endsWith = StringHelper::endsWith($haystack, $needle);
        $this->assertSame($result, $endsWith);
    }

    public function endsWithData(): array
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
     * @dataProvider camelCaseData
     * @param $result
     * @param $input
     */
    public function testCamelCase($result, $input)
    {
        $toCamel = StringHelper::camelCase($input);
        $this->assertSame($result, $toCamel);
    }

    public function camelCaseData(): array
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
     * @dataProvider containsAllData
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
    public function containsAllData(): array
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

    public function testContainsAllExceptions()
    {
        // Test that empty array with a string in it returns an exception.
        $this->tester->expectThrowable(ErrorException::class, function (){
            StringHelper::containsAll('', ['']);
        });
    }

    /**
     * @dataProvider uppercaseFirstData
     * @param $result
     * @param $input
     */
    public function testUppercaseFirst($result, $input)
    {
        $uppercaseFirst = StringHelper::upperCaseFirst($input);
        $this->assertSame($result, $uppercaseFirst);
    }

    public function uppercaseFirstData(): array
    {
        return [
            ['Craftcms', 'craftcms'],
            ['2craftcms', '2craftcms'],
            [' craftcms', ' craftcms'],
            [' ', ' ']
        ];
    }


    /**
     * @dataProvider indexOfData
     * @param $result
     * @param $haystack
     * @param $needle
     */
    public function testIndexOf($result, $haystack, $needle)
    {
        $index = StringHelper::indexOf($haystack, $needle);
        $this->assertSame($result, $index);
    }
    public function indexOfData(): array
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

    public function testStringIndexException()
    {
        $this->tester->expectThrowable(ErrorException::class, function (){
            StringHelper::indexOf('', '');
        });
    }

    public function testSubstringCount()
    {
        $this->assertSame(2, StringHelper::countSubstrings('hello', 'l'));
        $this->assertSame(1, StringHelper::countSubstrings('😀😘', '😘'));
        $this->assertSame(3, StringHelper::countSubstrings('!@#$%^&*()^^', '^'));
        $this->assertSame(4, StringHelper::countSubstrings('    ', ' '));
    }

    /**
     * @dataProvider snakeCaseData
     * @param $result
     * @param $input
     */
    public function testToSnakeCase($result, $input)
    {
        $toSnake = StringHelper::toSnakeCase($input);
        $this->assertSame($result, $toSnake);
    }

    public function snakeCaseData(): array
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
     * @dataProvider mb4Data
     * @param $result
     * @param $input
     */
    public function testIsMb4($result, $input)
    {
        $isMb4 = StringHelper::containsMb4($input);
        $this->assertSame($result, $isMb4);
    }

    public function mb4Data(): array
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
        ];
    }

    /**
     * @dataProvider charsAsArrayData
     * @param $result
     * @param $input
     */
    public function testCharsAsArray($result, $input)
    {
        $charsArray = StringHelper::charsAsArray($input);
        $this->assertSame($result, $charsArray);
    }

    public function charsAsArrayData(): array
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
     * @dataProvider toAsciiData
     * @param $result
     * @param $input
     */
    public function testToAscii($result, $input)
    {
        $toAscii = StringHelper::toAscii($input);
        $this->assertSame($result, $toAscii);
    }

    public function toAsciiData(): array
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
     * @dataProvider firstData
     * @param $result
     * @param $input
     * @param $requiredChars
     */
    public function testFirst($result, $input, $requiredChars)
    {
        $stripped =  StringHelper::first($input, $requiredChars);
        $this->assertSame($result, $stripped);
    }

    public function firstData(): array
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
     * @dataProvider stripHtmlData
     * @param $result
     * @param $input
     */
    public function testHtmlStripping($result, $input)
    {
        $stripped = StringHelper::stripHtml($input);
        $this->assertSame($result, $stripped);
    }

    public function stripHtmlData(): array
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
     * @dataProvider uuidDataProvider
     * @param $result
     * @param $input
     */
    public function testIsUUID($result, $input)
    {
        $isUUID = StringHelper::isUUID($input);
        $this->assertSame($result, $isUUID);
    }

    public function uuidDataProvider(): array
    {
        return [
            [true, StringHelper::UUID()],
            [true, 'c3d6a75d-5b98-4048-8106-8cc2de4af159'],
            [true, 'c74e8f78-c052-4978-b0e8-77a307f7b946'],
            [true, '469e6ed2-f270-458a-a80e-173821fee715'],
            [false, '00000000-0000-0000-0000-000000000000'],
            [false, StringHelper::UUID().StringHelper::UUID()],
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
     * @dataProvider collapseWhitespaceData
     * @param $result
     * @param $input
     */
    public function testWhitespaceCollapse($result, $input)
    {
        $whitespaceGone = StringHelper::collapseWhitespace($input);
        $this->assertSame($result, $whitespaceGone);
    }
    public function collapseWhitespaceData(): array
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
     * @dataProvider whitespaceProvider
     * @param $result
     * @param $input
     */
    public function testIsWhitespace($result, $input)
    {
        $isWhitespace = StringHelper::isWhitespace($input);
        $this->assertSame($result, $isWhitespace);
        $this->assertInternalType('boolean', $isWhitespace);
    }

    public function whitespaceProvider(): array
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
     * @dataProvider splitData
     * @param        $result
     * @param        $input
     * @param string $splitter
     */
    public function testStringSplit($result, $input, $splitter = ',')
    {
        $splitString = StringHelper::split($input, $splitter);
        $this->assertSame($result, $splitString);
    }

    public function splitData(): array
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
     * @dataProvider delimitData
     * @param $result
     * @param $input
     * @param $delimited
     */
    public function testDelimit($result, $input, $delimited)
    {
        $delimitedString = StringHelper::delimit($input, $delimited);
        $this->assertSame($result, $delimitedString);
        $this->assertInternalType('string', $delimitedString);
    }

    public function delimitData(): array
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
     * @dataProvider ensureRightData
     * @param $result
     * @param $input
     * @param $ensure
     */
    public function testEnsureRight($result, $input, $ensure)
    {
        $this->assertSame($result, StringHelper::ensureRight($input, $ensure));
    }

    public function ensureRightData(): array
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
     * @dataProvider randomStringWithCharsData
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
        for ($i = 0; $i<$strLen; $i++) {
            if (mb_strpos($valid, $str[$i]) === false) {
                $this->fail('Invalid chars');
            }
        }
    }

    public function randomStringWithCharsData(): array
    {
        return [
            ['asdfghjklxcvbnmqwertyuiop', 10],
            ['1234567890', '22'],
            ['!@#$%^&*()_{}|:"<>?', 0],
            ['!@#$%^&*()_{}|:"<>?', 8],
            ['                           ', 8],
            'tabs' => ['              ', 4],
            ['asdfghjklxcvbnmqwertyuiop', '10']
        ];
    }

    /**
     * @dataProvider mb4EncodingProvider
     * @param $result
     * @param $input
     */
    public function testMb4Encoding($result, $input)
    {
        $mb4String = StringHelper::encodeMb4($input);
       $this->assertSame($result, $mb4String);
        $this->assertInternalType('string', $mb4String);

        $this->assertFalse(StringHelper::containsMb4($mb4String));
    }

    public function mb4EncodingProvider(): array
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
     * TODO: Might need more tests here.
     * @dataProvider convertToUtf8Data
     * @param $result
     * @param $input
     */
    public function testConvertToUtf8($result, $input)
    {
        $utf8 = StringHelper::convertToUtf8($input);
        $this->assertSame($result, $utf8);
    }
    public function convertToUtf8Data(): array
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
     * @dataProvider encDecData
     * @param $input
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testEncDec( $input)
    {
        $enc = StringHelper::encenc($input);
        $this->assertStringStartsWith('base64:', $enc);
        $this->assertSame($input, StringHelper::decdec($enc));
    }
    public function encDecData(): array
    {
        return [
            ['1234567890asdfghjkl'],
            ['😂😁'],
            ['!@#$%^&*()_+{}|:"<>?']
        ];
    }

    public function testAsciiCharMap()
    {
        $deArray = ['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü'];
        $this->assertArrayNotHasKey('de', StringHelper::asciiCharMap(false, 'de'));
        $deMap = StringHelper::asciiCharMap(true, 'de');
        foreach ($deArray as $deChar) {
            $this->assertArrayHasKey($deChar, $deMap);
        }
    }

    /**
     */
    public function testUUID()
    {
        $uuid = StringHelper::UUID();
        $this->assertSame(1, preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid));
        $this->assertSame(36, strlen($uuid));
    }

    /**
     * @dataProvider toStringData
     * @param $result
     * @param $input
     * @param $glue
     */
    public function testToString($result, $input, $glue = ',')
    {
        $string = StringHelper::toString($input, $glue);
        $this->assertSame($result, $string);
    }
    public function toStringData(): array
    {
        return [
            ['test', 'test'],
            ['', new stdClass()],
            ['ima string', new ToStringTest('ima string')],
            ['t,e,s,t', ['t', 'e', 's', 't']],
            ['t|e|s|t', ['t', 'e', 's', 't'], '|'],
        ];
    }

    /**
     * @dataProvider randomStringData
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
            $this->assertContains($char, $validChars);
        }
    }
    public function randomStringData(): array
    {
        return [
            [],
            [50, false],
            [55, true],
        ];
    }

    /**
     * @dataProvider toPascalCaseData
     * @param $result
     * @param $input
     */
    public function testToPascalCase($result, $input)
    {
        $pascal = StringHelper::toPascalCase($input);
        $this->assertSame($result, $pascal);
    }
    public function toPascalCaseData(): array
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
     * @dataProvider toCamelCaseData
     * @param $result
     * @param $input
     */
    public function testToCamelCase($result, $input)
    {
        $camel = StringHelper::toCamelCase($input);
        $this->assertSame($result, $camel);
    }
    public function toCamelCaseData(): array
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
     * @dataProvider toKebabCaseData
     * @param $result
     * @param $input
     */
    public function testToKebabCase($result, $input)
    {
        $kebab = StringHelper::toKebabCase($input);
        $this->assertSame($result, $kebab);
    }
    public function toKebabCaseData(): array
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
     * @dataProvider linesData
     * @param $result
     * @param $input
     */
    public function testLines($result, $input)
    {
        $lines = StringHelper::lines($input);
        $this->assertSame($result, count($lines));
    }
    public function linesData(): array
    {
        return [
            [4, 'test
             
             
             test'],
            [1, 'test <br> test'],
            [1, 'thesearetabs       notspaces'],
            [2, '😂
            😁'],
            [11, '
            
            
            
            
            
            
            
            
            
            ']
        ];
    }

    /**
     * @dataProvider toUppercaseData
     * @param $result
     * @param $input
     */
    public function testToUppercase($result, $input)
    {
        $uppercase = StringHelper::toUpperCase($input);
        $this->assertSame($result, $uppercase);
    }
    public function toUppercaseData(): array
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
     * @dataProvider trimData
     * @param $result
     * @param $input
     */
    public function testTrim($result, $input)
    {
        $trim = StringHelper::trim($input);
        $this->assertSame($result, $trim);
    }
    public function trimData(): array
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
     * @dataProvider toTitleCase
     * @param $result
     * @param $input
     */
    public function testToTitleCase($result, $input)
    {
        $toTitleCase = StringHelper::toTitleCase($input);
        $this->assertSame($result, $toTitleCase);
    }
    public function toTitleCase(): array
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
     * @dataProvider toLowerCaseData
     * @param $result
     * @param $input
     */
    public function testToLowerCase($result, $input)
    {
        $toLower = StringHelper::toLowerCase($input);
        $this->assertSame($result, $toLower);
    }
    public function toLowerCaseData(): array
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
     * @dataProvider titelizeData
     * @param $result
     * @param $input
     */
    public function testTitleize($result, $input)
    {
        $titelize = StringHelper::titleize($input);
        $this->assertSame($result, $titelize);
    }
    public function titelizeData(): array
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
     * @dataProvider swapCaseData
     * @param $result
     * @param $input
     */
    public function testSwapCase($result, $input)
    {
        $swap = StringHelper::swapCase($input);
        $this->assertSame($result, $swap);
    }
    public function swapCaseData(): array
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
     * @dataProvider substrData
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
    public function substrData(): array
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
}
