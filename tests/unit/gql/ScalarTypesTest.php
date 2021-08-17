<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft as Craft;
use craft\elements\Entry;
use craft\errors\GqlException;
use craft\fields\Date;
use craft\gql\directives\FormatDateTime;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\Number;
use craft\gql\types\QueryArgument;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo;
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
     * @dataProvider serializationDataProvider
     *
     * @param ScalarType $type
     * @param $testValue
     * @param $match
     * @throws \GraphQL\Error\Error
     */
    public function testSerialization(ScalarType $type, $testValue, $match)
    {
        self::assertSame($match, $type->serialize($testValue));
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingValueDataProvider
     *
     * @param ScalarType $type
     * @param $testValue
     * @param $match
     * @param $exceptionThrown
     * @throws \GraphQL\Error\Error
     */
    public function testParsingValue(ScalarType $type, $testValue, $match, $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException($exceptionThrown);
            $type->parseValue($testValue);
        } else {
            self::assertSame($match, $type->parseValue($testValue));
        }
    }

    /**
     * Test DateTime parsing value correctly.
     * @throws \GraphQL\Error\Error
     */
    public function testDateTimeParseValueAndLiteral()
    {
        $timeAsStr = (new \DateTime('now'))->format("Y-m-d H:i:s");

        $this->assertInstanceOf(\DateTime::class, (new DateTime())->parseValue($timeAsStr));
        $this->assertInstanceOf(\DateTime::class, (new DateTime())->parseLiteral(new StringValueNode(['value' => $timeAsStr])));
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingLiteralDataProvider
     *
     * @param ScalarType $type
     * @param $testValue
     * @param $match
     * @param $exceptionThrown
     * @throws \Exception
     */
    public function testParsingLiteral(ScalarType $type, $testValue, $match, $exceptionThrown)
    {
        if ($exceptionThrown) {
            $this->expectException($exceptionThrown);
            $type->parseLiteral($testValue);
        } else {
            self::assertSame($match, $type->parseLiteral($testValue));
        }
    }

    /**
     * Test the useSystemTimezoneForGraphQlDates setting.
     * 
     * @throws \GraphQL\Error\Error
     */
    public function testTimeZoneConfigSetting()
    {
        Craft::$app->setTimeZone('America/New_York');

        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateField = $this->make(Date::class, [
            'showTimeZone' => false,
            'handle' => 'fieldName'
        ]);
        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'fieldName'
        ]);
        $resolver = $dateField->getContentGqlType()['resolve'];
        $element = $this->make(Entry::class, [
            'getFieldValue' => function () use ($dateTime) { return clone $dateTime; }
        ]);

        $settingValue = Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone;
        $currentTimezone = Craft::$app->getTimeZone();

        // Make sure we don't use UTC
        $newTimezone = 'America/New_York';

        Craft::$app->setTimeZone($newTimezone);
        Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone = true;
        $value1 = $resolver($element, [], null, $resolveInfo);

        Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone = false;
        $value2 = $resolver($element, [], null, $resolveInfo);

        Craft::$app->getConfig()->getGeneral()->setGraphqlDatesToSystemTimeZone = $settingValue;

        $this->assertNotEquals($value1->getTimeZone(), $value2->getTimeZone());
        Craft::$app->setTimeZone($currentTimezone);
    }

    /**
     * @return array[]
     */
    public function serializationDataProvider()
    {
        $now = new \DateTime();

        GqlEntityRegistry::setPrefix('');

        return [
            [DateTime::getType(), 'testString', 'testString'],
            [DateTime::getType(), null, null],
            [DateTime::getType(), clone $now, $now->setTimezone(new \DateTimeZone(FormatDateTime::defaultTimezone()))->format(FormatDateTime::DEFAULT_FORMAT)],

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

    /**
     * @return array[]
     */
    public function parsingValueDataProvider()
    {
        GqlEntityRegistry::setPrefix('');

        return [
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

    /**
     * @return array[]
     */
    public function parsingLiteralDataProvider()
    {
        GqlEntityRegistry::setPrefix('');

        return [
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
