<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\validators;


use Codeception\Test\Unit;
use craft\test\mockclasses\models\ExampleModel;
use craft\validators\UriFormatValidator;

/**
 * Class UriFormatValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class UriFormatValidatorTest extends Unit
{
    /**
     * @var UriFormatValidator
     */
    protected $uriFormatValidator;

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
        $this->uriFormatValidator = new UriFormatValidator();
    }

    /**
     * @dataProvider validateAttributeData
     * @param $mustValidate
     * @param $input
     */
    public function testValidateAttribute($mustValidate, $input)
    {

    }
    public function validateAttributeData()
    {
        return [

        ];
    }
}