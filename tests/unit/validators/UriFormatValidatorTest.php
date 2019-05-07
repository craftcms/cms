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
 * @since 3.1
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
     * @param bool $requireSlug
     */
    public function testValidateAttribute($mustValidate, $input, $requireSlug = false)
    {
        $this->model->exampleParam = $input;
        $this->uriFormatValidator->requireSlug = $requireSlug;

        $validatorResult = $this->uriFormatValidator->validateAttribute($this->model, 'exampleParam');

        $this->assertNull($validatorResult);

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }
    public function validateAttributeData(): array
    {
        return [
            [true, ''],
            [true, '', true],
            [true, 'test', false],
            [true, 'slug', true],
            [false, 'entry/{test}/test', true],

        ];
    }
}