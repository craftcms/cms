<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\UriFormatValidator;
use UnitTester;

/**
 * Class UriFormatValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UriFormatValidatorTest extends TestCase
{
    /**
     * @var UriFormatValidator
     */
    protected UriFormatValidator $uriFormatValidator;

    /**
     * @var ExampleModel
     */
    protected ExampleModel $model;
    /*
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider validateAttributeDataProvider
     * @param bool $mustValidate
     * @param string $input
     * @param bool $requireSlug
     */
    public function testValidateAttribute(bool $mustValidate, string $input, bool $requireSlug = false): void
    {
        $this->model->exampleParam = $input;
        $this->uriFormatValidator->requireSlug = $requireSlug;

        $this->uriFormatValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
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
    protected function _before(): void
    {
        $this->model = new ExampleModel();
        $this->uriFormatValidator = new UriFormatValidator();
    }
}
