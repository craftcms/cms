<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\validators;


use Codeception\Test\Unit;
use craft\validators\ArrayValidator;
use craftunit\support\mockclasses\models\ExampleModel;

/**
 * Class ArrayValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ArrayValidatorTest extends Unit
{
    /**
     * @var ArrayValidator
     */
    protected $arrayValidator;

    /**
     * @var ExampleModel
     */
    protected $model;
    /*
     * @var \UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->model = new ExampleModel();
        $this->arrayValidator = new ArrayValidator(['count' => 5, 'max' => 10, 'min' => 4, 'tooFew' => 'aint got nuff', 'tooMany' => 'staahhpp', 'notEqual' => 'aint right']);

        // Test all variables are passed in and all good.
        $this->assertSame(5, $this->arrayValidator->count);
        $this->assertSame(10, $this->arrayValidator->max);
        $this->assertSame(4, $this->arrayValidator->min);
        $this->assertSame('aint got nuff', $this->arrayValidator->tooFew);
        $this->assertSame('staahhpp', $this->arrayValidator->tooMany);
        $this->assertSame('aint right', $this->arrayValidator->notEqual);
    }

    /**
     * Test that if messages arent provided when creating the array validator, they will be provided automatically.
     * @dataProvider paramsToTestOnEmpty
     * @param ArrayValidator $validator
     * @param $variableName
     */
    public function testMessagingOnEmptyInputArray(ArrayValidator $validator, $variableName)
    {
        $this->assertTrue((strlen($validator->$variableName) > 2));

        $this->assertInternalType('string', $validator->$variableName);
    }

    public function paramsToTestOnEmpty()
    {
        $newValidator = new ArrayValidator(['min' => 1, 'max' => 10, 'count' => 4]);

        return [
            [$newValidator, 'message'], [$newValidator, 'tooFew'], [$newValidator, 'tooMany'], [$newValidator, 'notEqual']
        ];
    }

    public function testCountArrayInputValue()
    {
        $newValidator = new ArrayValidator(['count' => [2, 5]]);
        $this->assertSame(2, $newValidator->min);
        $this->assertSame(5, $newValidator->max);

        // Make sure if count is empty array. $count is a null variable.
        $newValidator = new ArrayValidator(['count' => []]);
        $this->assertNull($newValidator->count);
    }

    /**
     * @dataProvider arrayValidatorValues
     * @param      $inputValue
     * @param bool $mustValidate
     */
    public function testValidation($inputValue, bool $mustValidate)
    {
        $countValue = $this->arrayValidator->count;
        $this->arrayValidator->count = null;

        $this->model->exampleParam = $inputValue;
        $result = $this->arrayValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }

        $this->model->clearErrors();
        $this->model->exampleParam = null;
        $this->arrayValidator->count = $countValue;
    }

    /**
     * TODO: Add a count validation method that validates arrays are within count and if are too big or small they throw an error. .
     * @param array $input
     */
    public function testCountValidation()
    {

    }

    public function arrayValidatorValues()
    {
        return [
            [[1, 2, 3, 4 ], true],
            [[1, 2, 3, 4, 5 ], true],
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ], true],
            [[[1, 1], [2, 2], [3, 3], 4, 5, 6, 7, 8, 9, 10 ], true],
            ['hello', false],
            [[1, 2], false]

        ];
    }
}