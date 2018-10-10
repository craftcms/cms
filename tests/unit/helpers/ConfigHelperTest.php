<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\helpers\ConfigHelper;
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
            [4, '4'],
            [5, 5],
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
}