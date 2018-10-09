<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\validators;


use Codeception\Test\Unit;
use craft\validators\HandleValidator;
use craftunit\support\mockclasses\models\ExampleModel;

/**
 * Class HandleValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class HandleValidatorTest extends Unit
{
    /**
     * @var HandleValidator
     */
    protected $handleValidator;

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
        $this->colorValidator = new HandleValidator();
    }

    public function testStaticConstants()
    {
        $this->assertSame('[a-zA-Z][a-zA-Z0-9_]*', HandleValidator::$handlePattern);
        $this->assertSame(
            [
                'attribute', 'attributeLabels','attributeNames', 'attributes', 'classHandle', 'content',
                'dateCreated', 'dateUpdated', 'false', 'fields', 'handle', 'id', 'n', 'name', 'no',
                'rawContent', 'rules', 'searchKeywords', 'section', 'this',
                'true', 'type', 'uid', 'value', 'y','yes',
            ],
            HandleValidator::$baseReservedWords
        );
    }

    public function testStaticConstantsArentAllowed()
    {
        foreach (HandleValidator::$baseReservedWords as $reservedWord) {
            $this->model->exampleParam = $reservedWord;
            $this->handleValidator->validateAttribute($this->model->exampleParam, 'exampleParam');

            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());

            $this->model->clearErrors();
            $this->model->exampleParam = null;
        }
    }
}