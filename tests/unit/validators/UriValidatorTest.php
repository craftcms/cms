<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\validators;

use Codeception\Test\Unit;
use craft\test\mockclasses\models\ExampleModel;
use craft\validators\UriValidator;

/**
 * Class UriValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class UriValidatorTest extends Unit
{
    /**
     * @var UriValidator
     */
    protected $uriValidator;

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
        $this->uriValidator = new UriValidator();
    }

    /**
     * @dataProvider validateValueDataProvider
     *
     * @param      $mustValidate
     * @param      $input
     * @param null $pattern
     */
    public function testValidateValue($mustValidate, $input, $pattern = null)
    {
        if ($pattern) {
            $this->uriValidator->pattern = $pattern;
        }

        $validates = $this->uriValidator->validate($input);

        if ($mustValidate) {
            $this->assertTrue($validates);
        } else {
            $this->assertFalse($validates);
        }
    }

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
}
