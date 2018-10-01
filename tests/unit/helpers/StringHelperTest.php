<?php
namespace craftunit\helpers;

use Codeception\Util\ReflectionHelper;
use craft\helpers\StringHelper;
use craftcms\tests\support\ReflectionSupport;
use yii\base\ErrorException;

/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 29/09/2018
 * Time: 16:45
 */

class StringHelperTest extends \Codeception\Test\Unit
{
    public function testStuff()
    {
        $this->assertSame('UTF-8', StringHelper::UTF8);
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
        $testPassed = false;
        try {
            StringHelper::containsAll('', ['']);
        } catch (ErrorException $exception){
            $testPassed = true;
        }
        $this->assertTrue($testPassed);

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
        $testPassed = false;
        try {
            StringHelper::indexOf('', '');
        } catch (ErrorException $exception){
            $testPassed = true;
        }
        $this->assertTrue($testPassed);
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
        $this->assertSame(['!', '@', '#', '$', '%', '^'], StringHelper::charsAsArray('!@#$%^'));
        $this->assertSame(['🎧', '𢵌', '😀', '😘', '⛄'], StringHelper::containsMb4('🎧𢵌😀😘⛄'));

    }
}