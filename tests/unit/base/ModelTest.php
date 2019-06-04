<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\models;

use Codeception\Test\Unit;
use Craft;
use craft\test\mockclasses\models\ExampleModel;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Unit tests for ModelTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ModelTest extends Unit
{
    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * Tests a model for errors.
     *
     * @dataProvider hasErrorsDataProvider
     *
     * @param $result
     * @param $input
     * @param $searchParam
     * @param $paramName
     */
    public function testHasErrors($result, $input, $searchParam, $paramName)
    {
        $model1 = new ExampleModel();
        $model1->addError($paramName, $input);

        $this->assertSame($result, $model1->hasErrors($searchParam));
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

        $this->assertSame($dateTime->format('Y-m-d H:i:s'), $model->$paramName->format('Y-m-d H:i:s'));
        $this->assertSame($dateTime->getTimezone()->getName(), $model->$paramName->getTimezone()->getName());
    }

    /**
     * Test that if you pass in a MySql date string to a param not specified in dateTimeAttributes it is left alone(Not converted to \DateTime)
     */
    public function testAutomaticDetectionOfDatesDoesntHappen()
    {
        $model = new ExampleModel(['exampleParam' => '2018-11-12 20:00:00']);

        $this->assertSame('2018-11-12 20:00:00', $model->exampleParam);
    }

    /**
     * Test that if you create an empty model and then set the param it isn't converted to \DateTime
     */
    public function testRetroFittingDoesntWork()
    {
        $model = new ExampleModel();
        $model->exampleDateParam = '2018-11-12 20:00:00';

        $this->assertSame('2018-11-12 20:00:00', $model->exampleDateParam);
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

        $this->assertCount(1, $model1->getErrors());
        $this->assertCount(1, $model1->getErrors()['exampleParam']);

        $this->assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
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

        $this->assertCount(1, $model1->getErrors());
        $this->assertCount(2, $model1->getErrors()['exampleParam']);

        $this->assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
        $this->assertSame('alsoAintGood', $model1->getErrors()['exampleParam'][1]);
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

        $this->assertCount(2, $model1->getErrors());
        $this->assertCount(1, $model1->getErrors()['exampleParam']);
        $this->assertCount(1, $model1->getErrors()['-custom-.exampleParam']);

        $this->assertSame('thisAintGood', $model1->getErrors()['exampleParam'][0]);
        $this->assertSame('alsoAintGood', $model1->getErrors()['-custom-.exampleParam'][0]);
    }

    // Data Providers
    // =========================================================================

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
            [true, 'error', 'fields.*', 'fields[body]'],
            [true, 'error', 'fields.*', 'fields.body'],
            [true, 'error', 'fields.*', 'fields[body'],
            [true, 'error', 'fields.*', 'fields.[body'],
            [true, 'error', 'fields.*', 'fields.[body]'],

            [true, 'error', 'exampleParam', 'exampleParam'],
        ];
    }
}
