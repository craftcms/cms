<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\validators;

use Codeception\Test\Unit;
use craft\test\mockclasses\models\ExampleModel;
use craft\validators\HandleValidator;

/**
 * Class HandleValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HandleValidatorTest extends Unit
{
    // Public Methods
    // =========================================================================

    /**
     * @var HandleValidator
     */
    protected $handleValidator;

    /**
     * @var ExampleModel
     */
    protected $model;

    /*
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var array
     */
    protected static $reservedWords = ['bird', 'is', 'the', 'word'];

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    public function testStaticConstants()
    {
        $this->assertSame('[a-zA-Z][a-zA-Z0-9_]*', HandleValidator::$handlePattern);
        $this->assertSame(
            [
                'attribute', 'attributeLabels', 'attributeNames', 'attributes', 'classHandle', 'content',
                'dateCreated', 'dateUpdated', 'false', 'fields', 'handle', 'id', 'n', 'name', 'no',
                'rawContent', 'rules', 'searchKeywords', 'section', 'this',
                'true', 'type', 'uid', 'value', 'y', 'yes',
            ],
            HandleValidator::$baseReservedWords
        );
    }

    /**
     *
     */
    public function testStaticConstantsArentAllowed()
    {
        foreach (self::$reservedWords as $reservedWord) {
            $this->model->exampleParam = $reservedWord;
            $this->handleValidator->validateAttribute($this->model, 'exampleParam');

            $this->assertArrayHasKey('exampleParam', $this->model->getErrors(), $reservedWord);

            $this->model->clearErrors();
            $this->model->exampleParam = null;
        }
    }

    /**
     * @dataProvider handleValidationDataProvider
     *
     * @param bool $mustValidate
     * @param      $input
     */
    public function testHandleValidation(bool $mustValidate, $input)
    {
        $this->model->exampleParam = $input;

        $validatorResult = $this->handleValidator->validateAttribute($this->model, 'exampleParam');

        $this->assertNull($validatorResult);

        if ($mustValidate) {
            $this->assertArrayNotHasKey('exampleParam', $this->model->getErrors());
        } else {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        }
    }

    // Data Providers
    // =========================================================================

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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        $this->model = new ExampleModel();
        $this->handleValidator = new HandleValidator(['reservedWords' => self::$reservedWords]);

        $this->assertSame(self::$reservedWords, $this->handleValidator->reservedWords);
        self::$reservedWords = array_merge(self::$reservedWords, HandleValidator::$baseReservedWords);
    }
}
