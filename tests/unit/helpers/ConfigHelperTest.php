<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\ConfigHelper;
use craft\test\TestCase;
use DateTime;
use stdClass;
use UnitTester;
use yii\base\InvalidConfigException;

/**
 * Class ConfigHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ConfigHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider sizeInBytesDataProvider
     * @param int|float $expected
     * @param int|string $value
     */
    public function testSizeInBytes(int|float $expected, int|string $value): void
    {
        self::assertSame($expected, ConfigHelper::sizeInBytes($value));
    }

    /**
     * @dataProvider durationInSecondsDataProvider
     * @param int $expected
     * @param mixed $value
     * @throws InvalidConfigException
     */
    public function testDurationInSeconds(int $expected, mixed $value): void
    {
        self::assertContains(ConfigHelper::durationInSeconds($value), [
            $expected,
            $expected + (60 * 60),
            $expected - (60 * 60),
        ]);
    }

    /**
     *
     */
    public function testDurationSecondsException(): void
    {
        $this->tester->expectThrowable(
            InvalidConfigException::class, function() {
                ConfigHelper::durationInSeconds(true);
            });

        $this->tester->expectThrowable(InvalidConfigException::class, function() {
            ConfigHelper::durationInSeconds(['test' => 'test']);
        });

        $this->tester->expectThrowable(InvalidConfigException::class, function() {
            $dateTime = new DateTime('2018-08-08 20:0:00');
            ConfigHelper::durationInSeconds($dateTime);
        });

        $this->tester->expectThrowable(InvalidConfigException::class, function() {
            $std = new stdClass();
            $std->a = 'a';
            ConfigHelper::durationInSeconds($std);
        });
    }

    /**
     * @dataProvider localizedValueDataProvider
     * @param mixed $expected
     * @param mixed $value
     * @param string|null $siteHandle
     */
    public function testLocalizedValue(mixed $expected, mixed $value, ?string $siteHandle = null): void
    {
        self::assertSame($expected, ConfigHelper::localizedValue($value, $siteHandle));
    }

    /**
     * @return array
     */
    public static function localizedValueDataProvider(): array
    {
        return [
            // Ensure if array that it is accessed by the handle and returns the value of the index.
            ['imavalue', ['imahandle' => 'imavalue'], 'imahandle'],

            // If variable is callable.  Ensure the handle gets passed into the callable.
            [
                'imahandle', function($handle) {
                    return $handle;
                }, 'imahandle',
            ],
            ['imnotavalue', ['imnotahandle' => 'imnotavalue', 'anotherkey' => 'anothervalue'], 'imahandle'],
            ['string', 'string'],
            ['', ''],
            [null, []],
            [123, 123],
            [false, false],
            [true, true],
            [12345678901234567890, 12345678901234567890],

        ];
    }

    /**
     * @return array
     */
    public static function sizeInBytesDataProvider(): array
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
        ];
    }

    /**
     * @return array
     */
    public static function durationInSecondsDataProvider(): array
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
}
