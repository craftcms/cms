<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\ConfigHelper;
use craft\test\mockclasses\models\ExampleModel;
use DateTime;
use stdClass;
use UnitTester;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;

/**
 * Class ConfigHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class ConfigHelperTest extends Unit
{
    /**
     * @var UnitTester
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

    public function sizeInBytesData(): array
    {
        return [
            [5368709120, '5G'],
            [5242880, '5M'],
            [5120, '5K'],
            [5120, 'ABCDEFHIJFLKNOPQRSTUVWXYZ5K'],
            [5, '5ABCDEFHIJFKLKNOPQRSTUVWXYZ'],
            [5120, '!@#$%^5K&*()'],
            [4, '4'],
            [5, 5],
            [0, 'M5'],
            [0, false],
            [1, true],
            [0, null],
        ];
    }


    /**
     * @dataProvider durationInSecondsData
     * @param $result
     * @param $input
     * @throws InvalidConfigException
     */
    public function testDurationInSeconds($result, $input)
    {
        $durationResult = ConfigHelper::durationInSeconds($input);
        $this->assertSame($result, $durationResult);
        $this->assertIsInt($durationResult);
    }

    public function durationInSecondsData(): array
    {
        return [
            [86400, 'P1D'],
            [90000, 'P1DT1H'],
            [2, 2],
            [12312, 12312],
            [1, 1],
            [0, 0],
            [0, false],
            [0, '0'],
        ];
    }

    public function testDurationSecondsException()
    {
        $this->tester->expectThrowable(
            InvalidConfigException::class, function () {
                ConfigHelper::durationInSeconds(true);
        });

        $this->tester->expectThrowable(ErrorException::class, function (){
            ConfigHelper::durationInSeconds(['test' => 'test']);
        });

        $this->tester->expectThrowable(ErrorException::class, function (){
            $dateTime = new DateTime('2018-08-08 20:0:00');
            ConfigHelper::durationInSeconds($dateTime);
        });

        $this->tester->expectThrowable(ErrorException::class, function (){
            $std = new stdClass();
            $std->a = 'a';
            ConfigHelper::durationInSeconds($std);
        });
    }

    /**
     * @dataProvider localizedValueData
     *
     * @param $result
     * @param $input
     * @param null $handle
     */
    public function testLocalizedValue($result, $input, $handle = null)
    {
        $this->assertSame($result, ConfigHelper::localizedValue($input, $handle));
    }

    public function localizedValueData(): array
    {
        $exampleModel = new ExampleModel();
        $exampleModel->exampleParam = 'imaparam';

        return [
            // Ensure if array that it is accessed by the handle and returns the value of the index.
            ['imavalue', ['imahandle' => 'imavalue'], 'imahandle'],

            // If variable is callable.  Ensure the handle gets passed into the callable.
            ['imahandle', function($handle){  return $handle; }, 'imahandle'],
            ['imaparam', $exampleModel],
            [reset($exampleModel), $exampleModel],
            ['imnotavalue', ['imnotahandle' => 'imnotavalue', 'anotherkey' => 'anothervalue'], 'imahandle'],
            ['string', 'string'],
            ['', ''],
            [null, []],
            [123, 123],
            [false, false],
            [true, true],
            [12345678901234567890,12345678901234567890 ],

        ];
    }
}
