<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\MoneyHelper;
use craft\test\TestCase;
use Money\Currency;
use Money\Money;

/**
 * Unit tests for the Money Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MoneyHelperTest extends TestCase
{
    /**
     * @dataProvider toMoneyDataProvider
     * @param mixed $value
     * @param Money|false $expected
     */
    public function testToMoney(mixed $value, Money|false $expected): void
    {
        self::assertEquals($expected, MoneyHelper::toMoney($value));
    }

    /**
     * @dataProvider toDecimalDataProvider
     * @param mixed $value
     * @param mixed $expected
     * @return void
     */
    public function testToDecimal(mixed $value, mixed $expected): void
    {
        self::assertEquals($expected, MoneyHelper::toDecimal($value));
    }

    /**
     * @dataProvider toNumberDataProvider
     * @param mixed $value
     * @param mixed $expected
     * @param string|null $locale
     * @return void
     */
    public function testToNumber(mixed $value, mixed $expected, ?string $locale = null): void
    {
        if ($locale) {
            $oldLocale = Craft::$app->getFormattingLocale()->id;
            Craft::$app->getFormattingLocale()->id = $locale;
        }

        self::assertEquals($expected, MoneyHelper::toNumber($value));

        if ($locale) {
            Craft::$app->getFormattingLocale()->id = $oldLocale;
        }
    }

    /**
     * @dataProvider toStringDataProvider
     * @param mixed $value
     * @param mixed $expected
     * @param string|null $locale
     * @return void
     */
    public function testToString(mixed $value, mixed $expected, ?string $locale = null): void
    {
        self::assertEquals($expected, MoneyHelper::toString($value, $locale));
    }

    /**
     * @return array[]
     */
    public function toStringDataProvider(): array
    {
        return [
            [null, false, null],
            ['1,234.56', '1,234.56', null],
            [Money::USD('123456'), '$1,234.56', null],
            [Money::USD('123456'), "US$\xc2\xa01.234,56", 'nl'],
        ];
    }

    /**
     * @return array[]
     */
    public function toNumberDataProvider(): array
    {
        return [
            [null, false, null],
            ['1,234.56', '1,234.56', null],
            [Money::USD('123456'), '1,234.56', null],
            [Money::USD('123456'), '1.234,56', 'nl'],
        ];
    }

    /**
     * @return array[]
     */
    public function toDecimalDataProvider(): array
    {
        return [
            [Money::USD('123456'), '1234.56'],
            [Money::JPY('123456'), '123456'],
            [Money::BHD('123456'), '123.456'],
            [null, false],
        ];
    }

    /**
     * @return array
     */
    public function toMoneyDataProvider(): array
    {
        return [
            [null, false],
            [Money::USD(100), new Money(100, new Currency('USD'))],
            [['value' => '1.25', 'currency' => 'USD'], Money::USD('125')],
            [['value' => '1.234,56', 'currency' => 'USD', 'locale' => 'nl'], Money::USD('123456')],
        ];
    }
}
