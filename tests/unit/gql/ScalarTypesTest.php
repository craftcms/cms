<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\errors\GqlException;
use craft\gql\directives\FormatDateTime;
use craft\gql\types\DateTime;
use craft\gql\types\Number;
use craft\gql\types\QueryArgument;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class ScalarTypesTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Test the serialization of scalar data types
     *
     *@dataProvider seializationDataProvider
     */
    public function testSerialization(ScalarType $type, $testValue, $match)
    {
        $this->assertSame($match, $type->serialize($testValue));
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingValueDataProvider
     */
    public function testParsingValue(ScalarType $type, $testValue, $match, $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException($exceptionThrown);
            $type->parseValue($testValue);
        } else {
            $this->assertSame($match, $type->parseValue($testValue));
        }
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingLiteralDataProvider
     */
    public function testParsingLiteral(ScalarType $type, $testValue, $match, $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException($exceptionThrown);
            $type->parseLiteral($testValue);
        } else {
            $this->assertSame($match, $type->parseLiteral($testValue));
        }
    }

    public function seializationDataProvider()
    {
        $now = new \DateTime();

        return [
            [DateTime::getType(), 'testString', 'testString'],
            [DateTime::getType(), null, null],
            [DateTime::getType(), clone $now, $now->setTimezone(new \DateTimeZone(FormatDateTime::DEFAULT_TIMEZONE))->format(FormatDateTime::DEFAULT_FORMAT)],

            [Number::getType(), 'testString', 'testString'],
            [Number::getType(), '', null],
            [Number::getType(), 8, 8],
            [Number::getType(), '8', 8],
            [Number::getType(), '8.2', 8.2],
            [Number::getType(), '8.0', 8],
            [Number::getType(), 8.2, 8.2],
            [Number::getType(), '8,0', '8,0'],
            [Number::getType(), '0', 0],

            [QueryArgument::getType(), 'testString', 'testString'],
            [QueryArgument::getType(), 2, 2],
            [QueryArgument::getType(), true, true],
            [QueryArgument::getType(), 2.9, '2.9'],
        ];
    }

    public function parsingValueDataProvider()
    {
        return [
            [DateTime::getType(), $time = time(), (string)$time, false],

            [Number::getType(), 2, 2, false],
            [Number::getType(), 2.0, 2.0, false],
            [Number::getType(), null, null, false],
            [Number::getType(), 'oops', null, GqlException::class],

            [QueryArgument::getType(), 2, 2, false],
            [QueryArgument::getType(), 'ok', 'ok', false],
            [QueryArgument::getType(), true, true, false],
            [QueryArgument::getType(), 2.0, null, GqlException::class],

        ];
    }

    public function parsingLiteralDataProvider()
    {
        return [
            [DateTime::getType(), new StringValueNode(['value' => $time = time()]), (string)$time, false],
            [DateTime::getType(), new IntValueNode(['value' => 2]), null, GqlException::class],

            [Number::getType(), new StringValueNode(['value' => '2.4']), 2.4, false],
            [Number::getType(), new StringValueNode(['value' => 'fake']), 0.0, false],
            [Number::getType(), new FloatValueNode(['value' => 2.4]), 2.4, false],
            [Number::getType(), new IntValueNode(['value' => 2]), 2, false],
            [Number::getType(), new NullValueNode([]), null, false],
            [Number::getType(), new BooleanValueNode(['value' => false]), null, GqlException::class],

            [QueryArgument::getType(), new StringValueNode(['value' => '2']), '2', false],
            [QueryArgument::getType(), new IntValueNode(['value' => 2]), 2, false],
            [QueryArgument::getType(), new BooleanValueNode(['value' => true]), true, false],
            [QueryArgument::getType(), new FloatValueNode(['value' => '2']), null, GqlException::class],

        ];
    }
}
