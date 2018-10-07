<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craftunit\validators;

use Codeception\Test\Unit;
use craft\validators\ColorValidator;
use craftunit\support\mockclasses\models\ExampleModel;
use yii\base\Model;

/**
 * Class ColorValidatorTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
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
     * @dataProvider colorNormalizationData
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

    public function colorNormalizationData()
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
        $this->tester->expectException(\ErrorException::class, function (){
            ColorValidator::normalizeColor('');
        });
    }

    /**
     * @dataProvider colorValidatorAttributes
     * @param $result
     * @param $input
     * @param $attributeName
     */
    public function testAttributeValidation($input, $attributeName, bool $mustValidate)
    {
        $this->model->$attributeName = $input;

        $this->colorValidator->validateAttribute($this->model, $attributeName);

        if (!$mustValidate) {
            $this->assertArrayHasKey($attributeName, $this->model->getErrors());
        } else{
            $this->assertArrayNotHasKey($attributeName, $this->model->getErrors());
        }

        $this->model->clearErrors();
        $this->model->$attributeName = null;
    }

    public function colorValidatorAttributes()
    {
        return [
            ['xxx', 'exampleParam', false]
        ];
    }

}