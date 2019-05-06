<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\validators;

use Codeception\Test\Unit;
use craft\validators\UsernameValidator;
use UnitTester;


/**
 * Class UsernameValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class UsernameValidatorTest extends Unit
{
    /**
     * @var UsernameValidator
     */
    protected $usernameValidator;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->usernameValidator = new UsernameValidator();
    }

    /**
     * @dataProvider validateValueData
     * @param $result
     * @param $input
     */
    public function testValidateValue($result, $input)
    {
        $validated = $this->usernameValidator->validateValue($input);
        $this->assertSame($result, $validated);
    }
    public function validateValueData()
    {
        return [
            [null, 'asdfghjkl1234567890'],
            [['{attribute} cannot contain spaces.', []], '  '],
            [null, null],
            [null, '!@#$%^&*()'],
            [['{attribute} cannot contain spaces.', []], 'dsasadsdasasad    adsdasdassdasad'],
        ];
    }
}