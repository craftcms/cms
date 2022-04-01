<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\test\TestCase;
use craft\validators\UsernameValidator;
use yii\base\NotSupportedException;

/**
 * Class UsernameValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UsernameValidatorTest extends TestCase
{
    /**
     * @var UsernameValidator
     */
    protected UsernameValidator $usernameValidator;

    /**
     * @dataProvider validateValueDataProvider
     * @param array|null $expected
     * @param string|null $value
     * @throws NotSupportedException
     */
    public function testValidateValue(?array $expected, ?string $value): void
    {
        self::assertSame($expected, $this->usernameValidator->validateValue($value));
    }

    public function validateValueDataProvider(): array
    {
        return [
            [null, 'asdfghjkl1234567890'],
            [['{attribute} cannot contain spaces.', []], '  '],
            [null, null],
            [null, '!@#$%^&*()'],
            [['{attribute} cannot contain spaces.', []], 'dsasadsdasasad    adsdasdassdasad'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->usernameValidator = new UsernameValidator();
    }
}
