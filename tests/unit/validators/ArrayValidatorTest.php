<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\ArrayValidator;

/**
 * Class ArrayValidator.
 *
 * @todo Test the validateValue() function using $this->model->validate();
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ArrayValidatorTest extends TestCase
{
    /**
     * @var ArrayValidator
     */
    protected ArrayValidator $arrayValidator;

    /**
     * @var ExampleModel
     */
    protected ExampleModel $model;

    /**
     * Test that if messages aren't provided when creating the array validator (i.e. not setting the $tooMany message),
     * they will be provided automatically.
     *
     * @dataProvider paramsToTestOnEmptyDataProvider
     * @param ArrayValidator $validator
     * @param string $variableName
     */
    public function testMessagingOnEmptyInputArray(ArrayValidator $validator, string $variableName): void
    {
        self::assertTrue(strlen($validator->$variableName) > 2);

        self::assertIsString($validator->$variableName);
    }

    /**
     *
     */
    public function testCountArrayInputValue(): void
    {
        $newValidator = new ArrayValidator(['count' => [2, 5]]);
        self::assertSame(2, $newValidator->min);
        self::assertSame(5, $newValidator->max);

        // Make sure if count is empty array. $count is a null variable.
        $newValidator = new ArrayValidator(['count' => []]);
        self::assertNull($newValidator->count);
    }

    /**
     * @dataProvider arrayValidatorValuesDataProvider
     * @param mixed $inputValue
     * @param bool $mustValidate
     */
    public function testValidation(mixed $inputValue, bool $mustValidate): void
    {
        $this->arrayValidator->count = null;

        $this->model->exampleParam = $inputValue;
        $this->arrayValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    /**
     * Here we *specifically* test that if we pass in an array which as more than the minimum(4) and less than the maximum(10)
     * BUT that is more than the count(5) an error will still be thrown.
     */
    public function testCountValidation(): void
    {
        $this->model->exampleParam = [1, 2, 3, 4, 5, 6, 7];
        $this->arrayValidator->validateAttribute($this->model, 'exampleParam');

        self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        self::assertSame('aint right', $this->model->getErrors('exampleParam')[0]);
    }

    /**
     * @return array
     */
    public function paramsToTestOnEmptyDataProvider(): array
    {
        $newValidator = new ArrayValidator(['min' => 1, 'max' => 10, 'count' => 4]);

        return [
            [$newValidator, 'message'], [$newValidator, 'tooFew'], [$newValidator, 'tooMany'], [$newValidator, 'notEqual'],
        ];
    }

    /**
     * @return array
     */
    public function arrayValidatorValuesDataProvider(): array
    {
        return [
            [[1, 2, 3, 4], true],
            [[1, 2, 3, 4, 5], true],
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], true],
            [[[1, 1], [2, 2], [3, 3], 4, 5, 6, 7, 8, 9, 10], true],
            ['hello', false],
            [[1, 2], false],
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], false],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->model = new ExampleModel();
        $this->arrayValidator = new ArrayValidator(['count' => 5, 'max' => 10, 'min' => 4, 'tooFew' => 'aint got nuff', 'tooMany' => 'staahhpp', 'notEqual' => 'aint right']);

        // Test all variables are passed in and all good.
        self::assertSame(5, $this->arrayValidator->count);
        self::assertSame(10, $this->arrayValidator->max);
        self::assertSame(4, $this->arrayValidator->min);
        self::assertSame('aint got nuff', $this->arrayValidator->tooFew);
        self::assertSame('staahhpp', $this->arrayValidator->tooMany);
        self::assertSame('aint right', $this->arrayValidator->notEqual);
    }
}
