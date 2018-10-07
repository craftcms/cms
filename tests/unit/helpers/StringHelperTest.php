<?php
namespace craftunit\helpers;

use Codeception\Util\ReflectionHelper;
use craft\helpers\StringHelper;
use craftcms\tests\support\ReflectionSupport;
use yii\base\ErrorException;
use yii\db\Exception;

/**
 * Unit tests for the String Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class StringHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

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

    public function testEndsWith()
    {
        $this->assertTrue(StringHelper::endsWith('thisisastring a', 'a'));
        $this->assertTrue(StringHelper::endsWith('', ''));
        $this->assertTrue(StringHelper::endsWith('craft cms is awsome', 's awsome'));
        $this->assertFalse(StringHelper::endsWith('a ball is round', 'square'));
        $this->assertFalse(StringHelper::endsWith('a ball is round', 'ball'));

        $this->assertTrue(StringHelper::endsWith('😀😘', '😘'));
        $this->assertTrue(StringHelper::endsWith('29*@1*1209)*08231b**!@&712&(!&@', '!&@'));
        $this->assertTrue(StringHelper::endsWith('  ', ' '));
    }

    public function testToCamelCase()
    {
        $this->assertSame('craftCms', StringHelper::camelCase('Craft Cms'));

        $this->assertSame('cRAFTCMS', StringHelper::camelCase('CRAFT CMS'));
        $this->assertSame('cRAFTCMS', StringHelper::camelCase('CRAFTCMS'));
        $this->assertSame('', StringHelper::camelCase(''));
        $this->assertSame('😘', StringHelper::camelCase('😘'));
        $this->assertSame('22AlphaNNumeric', StringHelper::camelCase('22 AlphaN Numeric'));
        $this->assertSame('!@#$%^&*()', StringHelper::camelCase('!@#$%^&*()'));
        $this->assertSame('!@#$%^&*()', StringHelper::camelCase('!@#$%  ^&*()'));

        // Spaces are stripped
        $this->assertSame('', StringHelper::camelCase(' '));
    }

    public function testContainsAll()
    {
        $this->assertTrue(StringHelper::containsAll('haystack', ['haystack']));
        $this->assertTrue(StringHelper::containsAll('some haystackedy stack', ['stackedy']));
        $this->assertTrue(StringHelper::containsAll(' ', [' ']));

        // Case sensitivity check
        $this->assertFalse(StringHelper::containsAll('iam some text', ['tEXt']));
        $this->assertTrue(StringHelper::containsAll('iam some text', ['tEXt'], false));

        // Test that empty array with a string in it returns an exception.
        $this->tester->expectException(ErrorException::class, function (){
            StringHelper::containsAll('', ['']);
        });

        $this->assertFalse(StringHelper::containsAll('', []));
    }

    public function testUppercaseFirst()
    {
        $this->assertSame('Craftcms', StringHelper::upperCaseFirst('craftcms'));
        $this->assertSame('2craftcms', StringHelper::upperCaseFirst('2craftcms'));
        $this->assertSame(' craftcms', StringHelper::upperCaseFirst(' craftcms'));
        $this->assertSame(' ', StringHelper::upperCaseFirst(' '));
    }

    public function testWhitespace()
    {
        $this->assertTrue(StringHelper::isWhitespace(' '));
        $this->assertFalse(StringHelper::isWhitespace('    asd'));
        $this->assertTrue(StringHelper::isWhitespace('
        '));
        $this->assertTrue(StringHelper::isWhitespace(''));
    }

    public function testStringIndexCounter()
    {
        $this->assertSame(2, StringHelper::indexOf('thisisstring', 'is'));

        $this->assertSame(6, StringHelper::indexOf('craft cms', 'cms'));
        $this->assertSame(1, StringHelper::indexOf('😀😘', '😘'));
        $this->assertSame(2, StringHelper::indexOf('/@#$%^&*', '#'));
        $this->assertSame(0, StringHelper::indexOf('hello, people', 'he'));


        $this->assertFalse(StringHelper::indexOf('some string', 'a needle'));

        // Test that empty string returns an exception.
        $this->tester->expectException(ErrorException::class, function (){
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

    public function testToSnakeCase()
    {
        $this->assertSame('craft_cms', StringHelper::toSnakeCase('CRAFT CMS'));
        $this->assertSame('craftcms', StringHelper::toSnakeCase('CRAFTCMS'));
        $this->assertSame('', StringHelper::toSnakeCase(''));

        // TODO: Check on this. Why is strHelper removing emojis?
        $this->assertSame('', StringHelper::toSnakeCase('😘'));
        $this->assertSame('22_alpha_n_numeric', StringHelper::toSnakeCase('22 AlphaN Numeric'));

        // Test spaces are stripped.
        $this->assertSame('', StringHelper::toSnakeCase(' '));
    }

    public function testIsMb4()
    {
        $this->assertFalse(StringHelper::containsMb4('QWERTYUIOPASDFGHJKLZXCVBNM1234567890'));
        $this->assertFalse(StringHelper::containsMb4('!@#$%^&*()_'));
        $this->assertFalse(StringHelper::containsMb4('⛄'));
        $this->assertFalse(StringHelper::containsMb4(''));

        $this->assertTrue(StringHelper::containsMb4('😀😘'));
        $this->assertTrue(StringHelper::containsMb4('QWERTYUIOPASDFGHJKLZXCVBNM1234567890😘'));
        $this->assertTrue(StringHelper::containsMb4('!@#$%^&*()_🎧'));
        $this->assertTrue(StringHelper::containsMb4('!@#$%^&*()_𢵌'));
    }

    public function testCharsAsArray()
    {
        $this->assertSame([], StringHelper::charsAsArray(''));
        $this->assertSame(['a', 'b', 'c'], StringHelper::charsAsArray('abc'));
        $this->assertSame(['1', '2', '3'], StringHelper::charsAsArray('123'));
        $this->assertSame(['!', '@', '#', '$', '%', '^'], StringHelper::charsAsArray('!@#$%^'));
        $this->assertSame(['🎧', '𢵌', '😀', '😘', '⛄'], StringHelper::charsAsArray('🎧𢵌😀😘⛄'));
    }


    public function testToAscii()
    {
        $this->assertSame('', StringHelper::toAscii(''));
        $this->assertSame('abc', StringHelper::toAscii('abc'));
        $this->assertSame('123', StringHelper::toAscii('123'));
        $this->assertSame('!@#$%^', StringHelper::toAscii('!@#$%^'));
        $this->assertSame('', StringHelper::toAscii('🎧𢵌😀😘⛄'));
    }

    public function testFirst()
    {
        $this->assertSame('', StringHelper::first('', 1));
        $this->assertSame('qwertyuiopas', StringHelper::first('qwertyuiopasdfghjklzxcvbnm', 12));
        $this->assertSame('QWE', StringHelper::first('QWERTYUIOPASDFGHJKLZXCVBNM', 3));
        $this->assertSame('12', StringHelper::first('123456789', 2));
        $this->assertSame('!@#$%^', StringHelper::first('!@#$%^', 100));
        $this->assertSame('🎧𢵌', StringHelper::first('🎧𢵌😀😘⛄', 2));
    }

    public function testStripHtml()
    {
        $this->assertSame('hello', StringHelper::stripHtml('<p>hello</p>'));
        $this->assertSame('stuff', StringHelper::stripHtml('<>stuff</>'));
        $this->assertSame('craft', StringHelper::stripHtml('<script src="https://">craft</script>'));
        $this->assertSame('', StringHelper::stripHtml('<link src="#">'));
        $this->assertSame('stuff', StringHelper::stripHtml('<random-tag src="#">stuff</random-tag>'));
        $this->assertSame('stuff  ', StringHelper::stripHtml('<div><p>stuff  </p></div>'));
    }

    public function testIsUUID()
    {
        $this->assertTrue(StringHelper::isUUID(StringHelper::UUID()));
        $this->assertTrue(StringHelper::isUUID('c3d6a75d-5b98-4048-8106-8cc2de4af159'));
        $this->assertTrue(StringHelper::isUUID('c74e8f78-c052-4978-b0e8-77a307f7b946'));
        $this->assertTrue(StringHelper::isUUID('469e6ed2-f270-458a-a80e-173821fee715'));
        $this->assertTrue(StringHelper::isUUID('00000000-0000-0000-0000-000000000000'));
        $this->assertTrue(StringHelper::isUUID('  c3d6a75d-5b98-4048-8106-8cc2de4af159  '));
        // Sure this is right behaviour?
        $this->assertTrue(StringHelper::isUUID(StringHelper::UUID().StringHelper::UUID()));

        $this->assertFalse(StringHelper::isUUID('abc'));
        $this->assertFalse(StringHelper::isUUID('123'));
        $this->assertFalse(StringHelper::isUUID(''));
        $this->assertFalse(StringHelper::isUUID(' '));
        $this->assertFalse(StringHelper::isUUID('!@#$%^&*()'));
        $this->assertFalse(StringHelper::isUUID('469e6ed2-🎧𢵌😀😘-458a-a80e-173821fee715'));
        $this->assertFalse(StringHelper::isUUID('&*%!$^!#-5b98-4048-8106-8cc2de4af159'));
    }

    public function testCollapseWhitespace()
    {
        $this->assertSame('', StringHelper::collapseWhitespace('    '));
        $this->assertSame('', StringHelper::collapseWhitespace('                                           '));
        $this->assertSame('qwe rty uio pasd', StringHelper::collapseWhitespace('qwe rty     uio   pasd'));
        $this->assertSame('Q W E', StringHelper::collapseWhitespace('Q                     W E'));
        $this->assertSame('12345 67 89', StringHelper::collapseWhitespace('    12345   67     89     '));
        $this->assertSame('! @ #$ % ^', StringHelper::collapseWhitespace('! @     #$     %       ^'));
        $this->assertSame('🎧𢵌 😀😘⛄', StringHelper::collapseWhitespace('🎧𢵌       😀😘⛄       '));
    }

    public function testIsWhitespace()
    {
        $this->assertTrue(StringHelper::isWhitespace(''));
        $this->assertTrue(StringHelper::isWhitespace(' '));
        $this->assertTrue(StringHelper::isWhitespace('                                           '));
        $this->assertFalse(StringHelper::isWhitespace('qwe rty     uio   pasd'));
        $this->assertFalse(StringHelper::isWhitespace('Q                     W E'));
        $this->assertFalse(StringHelper::isWhitespace('    12345   67     89     '));
        $this->assertFalse(StringHelper::isWhitespace('! @     #$     %       ^'));
        $this->assertFalse(StringHelper::isWhitespace('🎧𢵌       😀😘⛄       '));
        $this->assertFalse(StringHelper::isWhitespace('craftcms'));
        $this->assertFalse(StringHelper::isWhitespace('😀😘'));
        $this->assertFalse(StringHelper::isWhitespace('/@#$%^&*'));
        $this->assertFalse(StringHelper::isWhitespace('hello,people'));
    }

    public function testSplit()
    {
        $this->assertSame(['22', '23'], StringHelper::split('22, 23'));
        $this->assertSame(['ab', 'cd'], StringHelper::split('ab,cd'));
        $this->assertSame(['22', '23'], StringHelper::split('22,23, '));
        $this->assertSame(['22', '23'], StringHelper::split('22| 23', '|'));
        $this->assertSame(['22,', '23'], StringHelper::split('22,/ 23', '/'));
        $this->assertSame(['22', '23'], StringHelper::split('22😀23', '😀'));
    }

    public function testDelimit()
    {
        $this->assertSame('', StringHelper::delimit('   ', '|'));
        $this->assertSame('hello|iam|astring', StringHelper::delimit('HelloIamAstring', '|'));
        $this->assertSame('😀😁😂🤣😃😄😅😆', StringHelper::delimit('😀😁😂🤣😃😄😅😆', '|'));
        $this->assertSame('hello iam astring', StringHelper::delimit('HelloIamAstring', ' '));
        $this->assertSame('hello!@#iam!@#astring', StringHelper::delimit('HelloIamAstring', '!@#'));
        $this->assertSame('hello😀😁😂iam😀😁😂astring', StringHelper::delimit('HelloIamAstring', '😀😁😂'));
        $this->assertSame('hello😀😁😂iam😀😁😂a2string', StringHelper::delimit('HelloIamA2string', '😀😁😂'));
    }

    /**
     * @dataProvider ensureRightData
     * @param $input
     */
    public function testEnsureRight($result, $input, $ensure)
    {
        $this->assertSame($result, StringHelper::ensureRight($input, $ensure));
    }

    public function ensureRightData()
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
     * @param $result
     * @param $valid
     * @param $length
     */
    public function testRandomStringWithChars($valid, int $length)
    {
        $str = StringHelper::randomStringWithChars($valid, $length);
        $strLen = \mb_strlen($str);

        $this->assertSame($length, $strLen);

        for ($i = 0; $i<$strLen; $i++) {
            if (\mb_strpos($valid, $str[$i]) === false) {
                $this->fail('Invalid chars');
            }
        }
    }

    public function randomStringWithCharsData()
    {
        return [
            ['asdfghjklxcvbnmqwertyuiop', 10],
            ['1234567890', '22'],
            ['!@#$%^&*()_{}|:"<>?', 0],
            ['!@#$%^&*()_{}|:"<>?', 8],

            'mb4' => ['🎧𢵌😀😘⛄', 2]

        ];
    }

    public function testToString()
    {
        //StringHelper::toString();
    }

    public function testUtf8Conversion()
    {
        //StringHelper::convertToUtf8();
    }



}