<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\validators;

use Codeception\Test\Unit;
use craft\validators\ColorValidator;
use craft\test\mockclasses\models\ExampleModel;
use ErrorException;

/**
 * Class ColorValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class ColorValidatorTest extends Unit
{
    /**
     * @var ColorValidator
     */
    protected $colorValidator;

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
        $this->colorValidator = new ColorValidator();
    }

    public function testPattern()
    {
        $this->assertSame('/^#[0-9a-f]{6}$/', $this->colorValidator->pattern);
    }

    /**
     * @dataProvider colorNormalizationDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testColorNormalization($result, $input)
    {
        $color = ColorValidator::normalizeColor($input);
        $this->assertSame($result, $color);

        $result = (mb_strpos($color, '#') !== false && mb_strlen($input) >= 0);
        $this->assertTrue($result);
    }

    public function colorNormalizationDataProvider(): array
    {
        return [
            ['#ffc10e', 'ffc10e'],
            ['#', '#'],
            ['#1234567890qwertyuiop!@#$%^&*()', '1234567890qwertyuiop!@#$%^&*()'],
            ['#12', '12'],
            ['#!!@@##', '!@#'],
            'three-chars-becomes-six' => ['#aassdd', 'asd'],
            ['#aassdd', 'ASD'],
            ['#a22d', 'a22d']
        ];
    }

    /**
     * Passing an empty string will return an exception.
     */
    public function testColorNormaliationException()
    {
        $this->tester->expectException(ErrorException::class, function (){
            ColorValidator::normalizeColor('');
        });
    }

    /**
     * @dataProvider colorValidatorAttributesDataProvider
     *
     * @param $input
     * @param bool $mustValidate
     */
    public function testAttributeValidation($input, bool $mustValidate)
    {
        $this->model->exampleParam = $input;

        $this->colorValidator->validateAttribute($this->model, 'exampleParam');

        if (!$mustValidate) {
            $this->assertArrayHasKey('exampleParam', $this->model->getErrors());
        } else{
            $this->assertSame([], $this->model->getErrors());
        }

        $this->model->clearErrors();
        $this->model->exampleParam = null;
    }

    public function colorValidatorAttributesDataProvider(): array
    {
        return [
            ['#ffc', true],
            ['#ffc10e', true],
            ['ffc10e', true],
            ['#ffc10eaaaaaaaaa', false],
            ['fffc10e', false],
            ['xxx', false],
            ['#ffc1', false],
            ['#ffc1e', false],
            ['#ff', false],
            ['#f', false],
            ['#', false],
            ['rgba(255, 0, 0, 0.2)', false],
            ['255, 0, 0, 0.2', false]
        ];
    }
}