<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\validators;

use Codeception\Test\Unit;
use Craft;
use craft\test\mockclasses\models\ExampleModel;
use craft\test\mockclasses\ToStringTest;
use craft\validators\UserPasswordValidator;
use UnitTester;
use yii\base\ErrorException;

/**
 * Class PasswordValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class PasswordValidatorTest extends Unit
{
    /**
     * @var UnitTester $tester
     */
    protected $tester;

    /**
     * @var UserPasswordValidator
     */
    protected $passwordValidator;

    /**
     * @var ExampleModel
     */
    protected $model;

    public function _before()
    {
        $this->passwordValidator = new UserPasswordValidator();
        $this->model = new ExampleModel();
    }

    /**
     * @dataProvider passwordValidationDataProvider
     *
     * @param      $inputValue
     * @param bool $mustValidate
     * @param string|null $currentPass
     */
    public function testValidation($inputValue, bool $mustValidate, string $currentPass = null)
    {
        $this->model->exampleParam = $inputValue;

        if ($currentPass) {
            $this->passwordValidator->currentPassword = $currentPass;
        }

        $this->passwordValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    public function passwordValidationDataProvider(): array
    {
        return [
            ['22', false],
            ['123456', true],
            ['!@#$%^&*()', true],
            ['161charsoaudsoidsaiadsjdsapoisajdpodsapaasdjosadojdsaodsapojdaposjosdakshjdsahksakhjhsadskajaskjhsadkdsakdsjhadsahkksadhdaskldskldslkdaslkadslkdsalkdsalkdsalkdsa', false]
        ];
    }

    /**
     * @dataProvider customConfigDataProvider
     *
     * @param $input
     * @param $mustValidate
     * @param $min
     * @param $max
     */
    public function testCustomConfig($input, $mustValidate, $min, $max)
    {
        $passVal = new UserPasswordValidator(['min' => $min, 'max' => $max]);
        $this->model->exampleParam = $input;
        $passVal->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }

    }
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
     * @dataProvider forceDiffValidationDataProvider
     *
     * @param $mustValidate
     * @param $input
     * @param $currentPassword
     */
    public function testForceDiffValidation($mustValidate, $input, $currentPassword)
    {
        $this->passwordValidator->forceDifferent = true;
        $this->passwordValidator->currentPassword = Craft::$app->getSecurity()->hashPassword($currentPassword);
        $this->model->exampleParam = $input;
        $this->passwordValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }
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
     * @dataProvider isEmptyDataProvider
     *
     * @param $result
     * @param $input
     * @param $isEmptyVal
     */
    public function testIsEmpty($result, $input, $isEmptyVal)
    {
        $this->passwordValidator->isEmpty = $isEmptyVal;
        $isEmpty = $this->passwordValidator->isEmpty($input);
        $this->assertSame($result, $isEmpty);
    }
    public function isEmptyDataProvider(): array
    {
        return [
            ['im a test', '', self::class.'::testReturn' ],
        ];
    }
    public function testToStringExpectException()
    {
        $passVal = $this->passwordValidator;
        $this->tester->expectThrowable(ErrorException::class, function () use ($passVal) {
            $passVal->isEmpty = 'craft_increment';
            $passVal->isEmpty(1);
        });
    }
    public static function testReturn(): string
    {
        return 'im a test';
    }

}
