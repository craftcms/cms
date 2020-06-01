<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use Codeception\Test\Unit;
use craft\test\mockclasses\models\ExampleModel;
use craft\validators\UriFormatValidator;

/**
 * Class UriFormatValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
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
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider validateAttributeDataProvider
     *
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

    /**
     * @return array
     */
    public function validateAttributeDataProvider(): array
    {
        return [
            [true, ''],
            [true, '', true],
            [true, 'test', false],
            [true, 'slug', true],
            [false, 'entry/{test}/test', true],

            // https://github.com/craftcms/cms/issues/4154
            [false, 'actions/{slug}', true],
            [false, 'actions', false],
            [false, 'adminustriggerus/foo', false],
            [false, 'adminustriggerus', false],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        $this->model = new ExampleModel();
        $this->uriFormatValidator = new UriFormatValidator();
    }
}
