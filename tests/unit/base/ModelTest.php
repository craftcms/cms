<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\base;

use Codeception\Test\Unit;
use Craft;
use craft\test\mockclasses\models\ExampleModel;
use DateTime;
use DateTimeZone;
use Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;

/**
 * Unit tests for ModelTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ModelTest extends Unit
{
    /**
     * Tests a model for errors.
     *
     * @dataProvider hasErrorsDataProvider
     *
     * @param bool $expected
     * @param string $attribute
     * @param string $error
     * @param string $searchParam
     */
    public function testHasErrors(bool $expected, string $attribute, string $error, string $searchParam)
    {
        $model1 = new ExampleModel();
        $model1->addError($attribute, $error);

        self::assertSame($expected, $model1->hasErrors($searchParam));
    }

    /**
     * Test the DateTimeAttributes function of the base Model
     *
     * @dataProvider dateTimeAttributesDataProvider
     *
     * @param $paramName
     * @param $dateForInput
     * @throws Exception
     */
    public function testDateTimeAttributes($paramName, $dateForInput)
    {
        $model = new ExampleModel([$paramName => $dateForInput]);

        $dateTime = new DateTime($dateForInput, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone(Craft::$app->getTimeZone()));

        self::assertSame($dateTime->format('Y-m-d H:i:s'), $model->$paramName->format('Y-m-d H:i:s'));
        self::assertSame($dateTime->getTimezone()->getName(), $model->$paramName->getTimezone()->getName());
    }

    /**
     * Test that if you pass in a MySql date string to a param not specified in dateTimeAttributes it is left alone(Not converted to \DateTime)
     */
    public function testAutomaticDetectionOfDatesDoesntHappen()
    {
        $model = new ExampleModel(['exampleParam' => '2018-11-12 20:00:00']);

        self::assertSame('2018-11-12 20:00:00', $model->exampleParam);
    }

    /**
     * Test that if you create an empty model and then set the param it isn't converted to \DateTime
     */
    public function testRetroFittingDoesntWork()
    {
        $model = new ExampleModel();
        $model->exampleDateParam = '2018-11-12 20:00:00';

        self::assertSame('2018-11-12 20:00:00', $model->exampleDateParam);
    }

    /**
     * Test string properties
     */
    public function testStringProperties(): void
    {
        self::assertSame(null, (new ExampleModel(['nullableStringParam' => '']))->nullableStringParam);
        self::assertSame(null, (new ExampleModel(['nullableStringParam' => null]))->nullableStringParam);
        self::assertSame('', (new ExampleModel(['stringParam' => null]))->stringParam);
        self::assertSame('foo', (new ExampleModel(['stringParam' => 'foo']))->stringParam);
        self::assertSame('1', (new ExampleModel(['stringParam' => 1]))->stringParam);
        self::expectException(InvalidConfigException::class);
        new ExampleModel(['stringParam' => []]);
    }

    /**
     * Test int properties
     */
    public function testIntProperties(): void
    {
        self::assertSame(null, (new ExampleModel(['nullableIntParam' => '']))->nullableIntParam);
        self::assertSame(null, (new ExampleModel(['nullableIntParam' => null]))->nullableIntParam);
        self::assertSame(0, (new ExampleModel(['intParam' => null]))->intParam);
        self::assertSame(0, (new ExampleModel(['intParam' => 'foo']))->intParam);
        self::assertSame(10, (new ExampleModel(['intParam' => '10']))->intParam);
        self::assertSame(10, (new ExampleModel(['intParam' => '10.1']))->intParam);
        self::expectException(InvalidConfigException::class);
        new ExampleModel(['intParam' => []]);
    }

    /**
     * Test float properties
     */
    public function testFloatProperties(): void
    {
        self::assertSame(null, (new ExampleModel(['nullableFloatParam' => '']))->nullableFloatParam);
        self::assertSame(null, (new ExampleModel(['nullableFloatParam' => null]))->nullableFloatParam);
        self::assertSame(0.0, (new ExampleModel(['floatParam' => null]))->floatParam);
        self::assertSame(0.0, (new ExampleModel(['floatParam' => 'foo']))->floatParam);
        self::assertSame(10.0, (new ExampleModel(['floatParam' => '10']))->floatParam);
        self::assertSame(10.1, (new ExampleModel(['floatParam' => '10.1']))->floatParam);
        self::expectException(InvalidConfigException::class);
        new ExampleModel(['floatParam' => []]);
    }

    /**
     * Test float properties
     */
    public function testNumericProperties(): void
    {
        self::assertSame(null, (new ExampleModel(['nullableNumericParam' => '']))->nullableNumericParam);
        self::assertSame(null, (new ExampleModel(['nullableNumericParam' => null]))->nullableNumericParam);
        self::assertSame(0, (new ExampleModel(['numericParam' => null]))->numericParam);
        self::assertSame(10, (new ExampleModel(['numericParam' => '10']))->numericParam);
        self::assertSame(10.1, (new ExampleModel(['numericParam' => '10.1']))->numericParam);
        self::expectException(InvalidConfigException::class);
        new ExampleModel(['numericParam' => []]);
    }

    /**
     * Test bool properties
     */
    public function testBoolProperties(): void
    {
        self::assertSame(null, (new ExampleModel(['nullableBoolParam' => '']))->nullableBoolParam);
        self::assertSame(null, (new ExampleModel(['nullableBoolParam' => null]))->nullableBoolParam);
        self::assertSame(false, (new ExampleModel(['boolParam' => null]))->boolParam);
        self::assertSame(true, (new ExampleModel(['boolParam' => 'foo']))->boolParam);
        self::assertSame(true, (new ExampleModel(['boolParam' => '10']))->boolParam);
        self::assertSame(true, (new ExampleModel(['boolParam' => true]))->boolParam);
        self::expectException(InvalidConfigException::class);
        new ExampleModel(['boolParam' => []]);
    }

    /**
     * Basic merge test
     */
    public function testMergingOfErrors()
    {
        $model1 = new ExampleModel();
        $model2 = new ExampleModel();
        $model2->addError('exampleParam', 'thisAintGood');

        $model1->addModelErrors($model2);

        self::assertCount(1, $model1->getErrors());
        self::assertCount(1, $model1->getErrors()['exampleParam']);

        self::assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
    }

    /**
     * What happens if both models have errors?
     */
    public function testMergingWithExistingParams()
    {
        $model1 = new ExampleModel();
        $model1->addError('exampleParam', 'thisAintGood');

        $model2 = new ExampleModel();
        $model2->addError('exampleParam', 'alsoAintGood');

        $model1->addModelErrors($model2);

        self::assertCount(1, $model1->getErrors());
        self::assertCount(2, $model1->getErrors()['exampleParam']);

        self::assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
        self::assertSame('alsoAintGood', $model1->getErrors()['exampleParam'][1]);
    }

    /**
     * Test what happens when we pass in an attribute prefix at addModelErrors.
     */
    public function testAttributePrefix()
    {
        $model1 = new ExampleModel();
        $model1->addError('exampleParam', 'thisAintGood');

        $model2 = new ExampleModel();
        $model2->addError('exampleParam', 'alsoAintGood');

        $model1->addModelErrors($model2, '-custom-');

        self::assertCount(2, $model1->getErrors());
        self::assertCount(1, $model1->getErrors()['exampleParam']);
        self::assertCount(1, $model1->getErrors()['-custom-.exampleParam']);

        self::assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
        self::assertSame('alsoAintGood', $model1->getErrors()['-custom-.exampleParam'][0]);
    }

    /**
     * @return array
     */
    public function dateTimeAttributesDataProvider(): array
    {
        return [
            // Craft defaults
            ['dateCreated', '2018-11-12 20:00:00'],
            ['dateUpdated', '2018-11-12 20:00:00'],

            // Added by ExampleModel
            ['exampleDateParam', '2018-11-12 20:00:00'],
        ];
    }

    /**
     * @return array
     */
    public function hasErrorsDataProvider(): array
    {
        return [
            [true, 'fields[body]', 'error', 'fields.*'],
            [true, 'fields.body', 'error', 'fields.*'],
            [true, 'fields[body', 'error', 'fields.*'],
            [true, 'fields.[body', 'error', 'fields.*'],
            [true, 'fields.[body]', 'error', 'fields.*'],
            [true, 'exampleParam', 'error', 'exampleParam'],
        ];
    }
}
