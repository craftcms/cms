<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft as Craft;
use craft\elements\Entry;
use craft\errors\GqlException;
use craft\fields\Date;
use craft\gql\directives\FormatDateTime;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\Money;
use craft\gql\types\Number;
use craft\gql\types\QueryArgument;
use craft\test\TestCase;
use DateTimeZone;
use Exception;
use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;

class ScalarTypesTest extends TestCase
{
    /**
     * Test the serialization of scalar data types
     *
     * @dataProvider serializationDataProvider
     * @param ScalarType $type
     * @param mixed $testValue
     * @param mixed $match
     * @throws Error
     */
    public function testSerialization(ScalarType $type, mixed $testValue, mixed $match): void
    {
        self::assertSame($match, $type->serialize($testValue));
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingValueDataProvider
     * @param ScalarType $type
     * @param mixed $testValue
     * @param mixed $match
     * @param string|null $exceptionThrown
     * @throws Error
     */
    public function testParsingValue(ScalarType $type, mixed $testValue, mixed $match, ?string $exceptionThrown = null): void
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
     *
     * @throws Error
     */
    public function testDateTimeParseValueAndLiteral(): void
    {
        $timeAsStr = (new \DateTime('now'))->format('Y-m-d H:i:s');

        $this->assertInstanceOf(\DateTime::class, (new DateTime())->parseValue($timeAsStr));
        $this->assertInstanceOf(\DateTime::class, (new DateTime())->parseLiteral(new StringValueNode(['value' => $timeAsStr])));
    }

    /**
     * Test parsing a value provided as a query variable
     *
     * @dataProvider parsingLiteralDataProvider
     * @param ScalarType $type
     * @param ValueNode $testValue
     * @param mixed $match
     * @param string|null $exceptionThrown
     * @throws Exception
     */
    public function testParsingLiteral(ScalarType $type, ValueNode $testValue, mixed $match, ?string $exceptionThrown = null): void
    {
        if ($exceptionThrown) {
            $this->expectException($exceptionThrown);
            /** @noinspection PhpParamsInspection */
            /** @phpstan-ignore-next-line */
            $type->parseLiteral($testValue);
        } else {
            /** @noinspection PhpParamsInspection */
            /** @phpstan-ignore-next-line */
            self::assertSame($match, $type->parseLiteral($testValue));
        }
    }

    /**
     * Test the useSystemTimezoneForGraphQlDates setting.
     *
     * @throws Error
     */
    public function testTimeZoneConfigSetting(): void
    {
        Craft::$app->setTimeZone('America/New_York');

        $dateTime = new \DateTime('now', new DateTimeZone('UTC'));
        $dateField = $this->make(Date::class, [
            'showTimeZone' => false,
            'handle' => 'fieldName',
        ]);
        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'fieldName',
        ]);
        $resolver = $dateField->getContentGqlType()['resolve'];
        $element = $this->make(Entry::class, [
            'getFieldValue' => function() use ($dateTime) {
                return clone $dateTime;
            },
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
    public function serializationDataProvider(): array
    {
        $now = new \DateTime();

        GqlEntityRegistry::setPrefix('');

        return [
            [DateTime::getType(), 'testString', 'testString'],
            [DateTime::getType(), null, null],
            [DateTime::getType(), clone $now, $now->setTimezone(new DateTimeZone(Craft::$app->getTimeZone()))->format(FormatDateTime::DEFAULT_FORMAT)],

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

            'money-1-dollar' => [Money::getType(), \Money\Money::USD(100), '$1.00'],
            'money-1-thousand-dollars' => [Money::getType(), \Money\Money::USD(123456), '$1,234.56'],
            'money-null' => [Money::getType(), null, null],
            'money-error' => [Money::getType(), 'testString', 'testString'],
        ];
    }

    /**
     * @return array[]
     */
    public function parsingValueDataProvider(): array
    {
        GqlEntityRegistry::setPrefix('');

        return [
            [Number::getType(), 2, 2],
            [Number::getType(), 2.0, 2.0],
            [Number::getType(), null, null],
            [Number::getType(), 'oops', null, GqlException::class],

            [QueryArgument::getType(), 2, 2],
            [QueryArgument::getType(), 'ok', 'ok'],
            [QueryArgument::getType(), true, true],
            [QueryArgument::getType(), 2.0, null, GqlException::class],

            [Money::getType(), 2, 2],
            [Money::getType(), 2.0, 2.0],
            [Money::getType(), null, null],
            [Money::getType(), -2.0, -2.0],
            [Money::getType(), 'err', null, GqlException::class],
        ];
    }

    /**
     * @return array[]
     */
    public function parsingLiteralDataProvider(): array
    {
        GqlEntityRegistry::setPrefix('');

        return [
            [DateTime::getType(), new IntValueNode(['value' => 2]), null, GqlException::class],

            [Number::getType(), new StringValueNode(['value' => '2.4']), 2.4],
            [Number::getType(), new StringValueNode(['value' => 'fake']), 0.0],
            [Number::getType(), new FloatValueNode(['value' => 2.4]), 2.4],
            [Number::getType(), new IntValueNode(['value' => 2]), 2],
            [Number::getType(), new NullValueNode([]), null],
            [Number::getType(), new BooleanValueNode(['value' => false]), null, GqlException::class],

            [QueryArgument::getType(), new StringValueNode(['value' => '2']), '2'],
            [QueryArgument::getType(), new IntValueNode(['value' => 2]), 2],
            [QueryArgument::getType(), new BooleanValueNode(['value' => true]), true],
            [QueryArgument::getType(), new FloatValueNode(['value' => '2']), null, GqlException::class],

            [Money::getType(), new StringValueNode(['value' => '2.4']), 2.4],
            [Money::getType(), new StringValueNode(['value' => 'fake']), 0.0],
            [Money::getType(), new FloatValueNode(['value' => 2.4]), 2.4],
            [Money::getType(), new IntValueNode(['value' => 2]), 2],
            [Money::getType(), new NullValueNode([]), null],
            [Money::getType(), new BooleanValueNode(['value' => false]), null, GqlException::class],
        ];
    }
}
