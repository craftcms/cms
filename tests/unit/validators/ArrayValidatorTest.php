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

    public function testMessagingOnEmptyInputArray()
    {
        $newValidator = new ArrayValidator([]);
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
    public function testValidation()
    {

    }

}