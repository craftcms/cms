<?php
namespace craftcms\tests\helpers;

use Codeception\Util\ReflectionHelper;
use craft\helpers\StringHelper;
use craftcms\tests\support\ReflectionSupport;

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
        $this->assertSame('craftCms', StringHelper::camelCase('CRAFT CMS'));
        $this->assertSame('craftcms', StringHelper::camelCase('CRAFTCMS'));
        $this->assertSame('', StringHelper::camelCase(''));
        $this->assertSame('😘', StringHelper::camelCase('😘'));
        $this->assertSame('22AlphaNNumeric', StringHelper::camelCase('22 AlphaN Numeric'));

        $this->assertSame(' ', StringHelper::camelCase(' '));
    }

    public function testContainsAll()
    {
        $this->assertTrue(StringHelper::containsAll('haystack', ['haystack']));
        $this->assertTrue(StringHelper::containsAll('some haystackedy stack', ['stackedy']));
        $this->assertTrue(StringHelper::containsAll(' ', [' ']));

        // Case sensitivity check
        $this->assertFalse(StringHelper::containsAll('iam some text', ['tEXt']));
        $this->assertTrue(StringHelper::containsAll('iam some text', ['tEXt'], false));

        $this->assertTrue(StringHelper::containsAll('', ['']));
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
        $this->assertCount(2, StringHelper::indexOf('thisisstring', 'is'));

        $this->assertCount(6, StringHelper::indexOf('craft cms', 'cms'));
        $this->assertCount(1, StringHelper::indexOf('😀😘', '😘'));
        $this->assertCount(2, StringHelper::indexOf('/@#$%^&*', '#'));
        $this->assertCount(0, StringHelper::indexOf('hello, people', 'he'));


        $this->assertFalse(StringHelper::indexOf('some string', 'a needle'));
        $this->assertCount(0, StringHelper::indexOf('', ''));
    }

    public function testSubstringCount()
    {
        $this->assertCount(2, StringHelper::countSubstrings('hello', 'l'));
        $this->assertCount(1, StringHelper::countSubstrings('😀😘', '😘'));
        $this->assertCount(3, StringHelper::countSubstrings('!@#$%^&*()^^', '^'));
        $this->assertCount(4, StringHelper::countSubstrings('    ', ' '));
    }

    public function testToSnakeCase()
    {
        $this->assertSame('craft_cms', StringHelper::camelCase('CRAFT CMS'));
        $this->assertSame('craftcms', StringHelper::camelCase('CRAFTCMS'));
        $this->assertSame('', StringHelper::camelCase(''));
        $this->assertSame('😘', StringHelper::camelCase('😘'));
        $this->assertSame('22_alpha_n_numeric', StringHelper::camelCase('22 AlphaN Numeric'));

        $this->assertSame(' ', StringHelper::camelCase(' '));
    }


}