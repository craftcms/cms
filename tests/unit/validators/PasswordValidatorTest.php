<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use Craft;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\UserPasswordValidator;
use TypeError;
use UnitTester;

/**
 * Class PasswordValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PasswordValidatorTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var UserPasswordValidator
     */
    protected UserPasswordValidator $passwordValidator;

    /**
     * @var ExampleModel
     */
    protected ExampleModel $model;

    /**
     * @dataProvider passwordValidationDataProvider
     * @param string $inputValue
     * @param bool $mustValidate
     * @param string|null $currentPass
     */
    public function testValidation(string $inputValue, bool $mustValidate, string $currentPass = null): void
    {
        $this->model->exampleParam = $inputValue;

        if ($currentPass) {
            $this->passwordValidator->currentPassword = $currentPass;
        }

        $this->passwordValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    /**
     * @dataProvider customConfigDataProvider
     * @param mixed $input
     * @param bool $mustValidate
     * @param int $min
     * @param int $max
     */
    public function testCustomConfig(mixed $input, bool $mustValidate, int $min, int $max): void
    {
        $passVal = new UserPasswordValidator(['min' => $min, 'max' => $max]);
        $this->model->exampleParam = $input;
        $passVal->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    /**
     * @dataProvider forceDiffValidationDataProvider
     * @param bool $mustValidate
     * @param string $input
     * @param string $currentPassword
     */
    public function testForceDiffValidation(bool $mustValidate, string $input, string $currentPassword): void
    {
        $this->passwordValidator->forceDifferent = true;
        $this->passwordValidator->currentPassword = Craft::$app->getSecurity()->hashPassword($currentPassword);
        $this->model->exampleParam = $input;
        $this->passwordValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    public function testToStringExpectException(): void
    {
        $passVal = $this->passwordValidator;

        $this->tester->expectThrowable(TypeError::class, function() use ($passVal) {
            /** @phpstan-ignore-next-line */
            $passVal->isEmpty = 'craft_increment';
            $passVal->isEmpty(1);
        });
    }

    /**
     * @return array
     */
    public function passwordValidationDataProvider(): array
    {
        return [
            ['22', false],
            ['123456', true],
            ['!@#$%^&*()', true],
            ['161charsoaudsoidsaiadsjdsapoisajdpodsapaasdjosadojdsaodsapojdaposjosdakshjdsahksakhjhsadskajaskjhsadkdsakdsjhadsahkksadhdaskldskldslkdaslkadslkdsalkdsalkdsalkdsa', false],
        ];
    }

    /**
     * @return array
     */
    public function customConfigDataProvider(): array
    {
        return [
            ['password', false, 0, 0],
            ['3', false, 2, 0],
            ['123', true, 3, 3],
            ['123', true, 2, 3],
            ['123', true, 3, 4],
            ['', true, -1, 0],
            [null, false, -1, 0],
        ];
    }

    /**
     * @return array
     */
    public function forceDiffValidationDataProvider(): array
    {
        return [
            [false, 'test', 'test'],
            [false, '', ''],
            // Not 6 chars
            [false, 'test', 'difftest'],
            [true, 'onetwothreefourfivesix', 'onetwothreefourfivesixseven'],
            // Spaces?
            [true, '      ', '         '],

        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->passwordValidator = new UserPasswordValidator();
        $this->model = new ExampleModel();
    }
}
