<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\ConfigHelper;
use craftunit\support\mockclasses\models\ExampleModel;
use PHPUnit\Util\ConfigurationGenerator;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;

/**
 * Class ConfigHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ConfigHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider sizeInBytesData
     *
     * @param $result
     * @param $input
     */
    public function testSizeInBytes($result, $input)
    {
        $this->assertSame($result, ConfigHelper::sizeInBytes($input));
    }

    public function sizeInBytesData()
    {
        return [
            [5368709120.0, '5G'],
            [5242880, '5M'],
            [5120, '5K'],
            [5120, 'ABCDEFHIJFLKNOPQRSTUVWXYZ5K'],
            [5, '5ABCDEFHIJFKLKNOPQRSTUVWXYZ'],
            [5120, '!@#$%^&*()5K'],
            [4, '4'],
            [5, 5],
            [0, 'M5'],
        ];
    }


    /**
     * @dataProvider durationInSecondsData
     */
    public function testDurationInSeconds($result, $input)
    {
        $durationResult = ConfigHelper::durationInSeconds($input);
        $this->assertSame($result, $durationResult);
        $this->assertInternalType('integer', $durationResult);
    }

    public function durationInSecondsData()
    {
        return [
            [86400, 'P1D'],
            [90000, 'P1DT1H'],
            [2, 2],
            [12312, 12312],
            [1, 1],
            [0, 0],
            [0, false],
            [0, ''],
            [0, '0'],
        ];
    }

    /**
     * TODO: Shouldnt these tests all return an InvalidConfigException
     */
    public function testDurationSecondsException()
    {
        $this->tester->expectException(ErrorException::class, function (){
            ConfigHelper::durationInSeconds(['test' => 'test']);
        });

        $this->tester->expectException(ErrorException::class, function (){
            ConfigHelper::durationInSeconds(new \DateTime('2018-08-08 20:0:00'));
        });

        $this->tester->expectException(ErrorException::class, function (){
            ConfigHelper::durationInSeconds(new \stdClass('2018-08-08 20:0:00'));
        });
    }

    /**
     * @dataProvider localizedValueData
     *
     * @param $result
     * @param $input
     */
    public function testLocalizedValue($result, $input, $handle = null)
    {
        $this->assertSame($result, ConfigHelper::localizedValue($input, $handle));
    }

    public function localizedValueData()
    {
        $exampleModel = new ExampleModel();
        $exampleModel->exampleParam = 'imaparam';

        return [
            // Ensure if array that it is accesed by the handle and returns the value of the index.
            ['imavalue', ['imahandle' => 'imavalue'], 'imahandle'],

            // If variable is callable.  Ensure the handle gets passed into the callable.
            ['imahandle', function($handle){  return $handle; }, 'imahandle'],
            ['imaparam', $exampleModel, null],
            [reset($exampleModel), $exampleModel, null],
            ['imnotavalue', ['imnotahandle' => 'imnotavalue'], 'imahandle'],
            ['string', 'string', null],
            ['', '', null],
            [123, 123, null],
            [false, false, null],
            [true, true, null],
            [12345678901234567890,12345678901234567890 , null],

        ];
    }
}