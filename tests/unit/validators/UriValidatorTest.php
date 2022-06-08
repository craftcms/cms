<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\UriValidator;

/**
 * Class UriValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UriValidatorTest extends TestCase
{
    /**
     * @var UriValidator
     */
    protected UriValidator $uriValidator;

    /**
     * @var ExampleModel
     */
    protected ExampleModel $model;

    /**
     * @dataProvider validateValueDataProvider
     * @param bool $mustValidate
     * @param mixed $input
     * @param string|null $pattern
     */
    public function testValidateValue(bool $mustValidate, mixed $input, string $pattern = null): void
    {
        if ($pattern) {
            $this->uriValidator->pattern = $pattern;
        }

        $validates = $this->uriValidator->validate($input);

        if ($mustValidate) {
            self::assertTrue($validates);
        } else {
            self::assertFalse($validates);
        }
    }

    /**
     * @return array
     */
    public function validateValueDataProvider(): array
    {
        return [
            [true, 'test'],
            [false, ' '],
            [false, ''],
            [false, null],
            [false, 'integer', '/^\w+\((\d+)\)/'],
            [true, 'integer(9)', '/^\w+\((\d+)\)/'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->model = new ExampleModel();
        $this->uriValidator = new UriValidator();
    }
}
