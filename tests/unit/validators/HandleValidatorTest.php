<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use craft\test\mockclasses\models\ExampleModel;
use craft\test\TestCase;
use craft\validators\HandleValidator;

/**
 * Class HandleValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HandleValidatorTest extends TestCase
{
    /**
     * @var HandleValidator
     */
    protected HandleValidator $handleValidator;

    /**
     * @var ExampleModel
     */
    protected ExampleModel $model;

    /**
     * @var array
     */
    protected static array $reservedWords = ['bird', 'is', 'the', 'word'];

    public function testStaticConstants(): void
    {
        self::assertSame('[a-zA-Z][a-zA-Z0-9_]*', HandleValidator::$handlePattern);
        self::assertSame(
            [
                'attribute', 'attributeLabels', 'attributeNames', 'attributes', 'classHandle', 'content',
                'dateCreated', 'dateUpdated', 'errors', 'false', 'fields', 'handle', 'id', 'n', 'name', 'no',
                'rawContent', 'rules', 'searchKeywords', 'section', 'this',
                'true', 'type', 'uid', 'value', 'y', 'yes',
            ],
            HandleValidator::$baseReservedWords
        );
    }

    /**
     *
     */
    public function testStaticConstantsArentAllowed(): void
    {
        foreach (self::$reservedWords as $reservedWord) {
            $this->model->exampleParam = $reservedWord;
            $this->handleValidator->validateAttribute($this->model, 'exampleParam');

            self::assertArrayHasKey('exampleParam', $this->model->getErrors(), $reservedWord);

            $this->model->clearErrors();
            $this->model->exampleParam = null;
        }
    }

    /**
     * @dataProvider handleValidationDataProvider
     * @param bool $mustValidate
     * @param string $input
     */
    public function testHandleValidation(bool $mustValidate, string $input): void
    {
        $this->model->exampleParam = $input;

        $this->handleValidator->validateAttribute($this->model, 'exampleParam');

        if ($mustValidate) {
            self::assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            self::assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    /**
     * @return array
     */
    public function handleValidationDataProvider(): array
    {
        return [
            [true, 'iamAHandle'],
            [true, 'iam1Handle'],
            [true, 'ASDFGHJKLQWERTYUIOPZXCVBNM'],
            [false, 'iam!Handle'],
            [false, '!@#$%^&*()'],
            [false, '🔥'],
            [false, '123'],
            [false, 'iam A Handle'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->model = new ExampleModel();
        $this->handleValidator = new HandleValidator(['reservedWords' => self::$reservedWords]);

        self::assertSame(self::$reservedWords, $this->handleValidator->reservedWords);
        self::$reservedWords = array_merge(self::$reservedWords, HandleValidator::$baseReservedWords);
    }
}
