<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\fields\Money;
use Money\Currency;
use UnitTester;

/**
 * Unit tests for the Money custom field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class MoneyTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider normalizeValueDataProvider
     * @param $money
     * @param string $value
     * @param string $currency
     * @param float|null $defaultValue
     * @param ElementInterface|null $element
     */
    public function testNormalizeValue($money, string $value, string $currency, ?float $defaultValue, ?ElementInterface $element): void
    {
        $moneyField = new Money();
        $moneyField->defaultValue = $defaultValue;
        $normalized  = $moneyField->normalizeValue($money, $element);

        self::assertInstanceOf(\Money\Money::class, $normalized);
        self::assertEquals($value, $normalized->getAmount());
    }

    /**
     * @dataProvider validateSubUnitsDataProvider
     */
    public function testValidateSubUnits(string $currency, string $value, bool $hasErrors): void
    {
        $moneyField = new Money();
        $moneyField->currency = $currency;
        $moneyField->defaultValue = $value;

        $moneyField->validateSubUnits('defaultValue');

        self::assertSame($hasErrors, $moneyField->hasErrors());
    }

    public function normalizeValueDataProvider(): array
    {
        $freshEntry = new Entry();
        $freshEntry->setIsFresh(true);

        return [
            'money-object' => [new \Money\Money(100, new Currency('USD')), '100', 'USD', null, null],
            'default-value' => [null, '123', 'USD', 1.23, $freshEntry],
            'array-passed' => [[
                'money' => '1,23',
                'locale' => 'nl'
            ], '123', 'USD', null, null],
        ];
    }

    /**
     * @return array[]
     */
    public function validateSubUnitsDataProvider(): array
    {
        return [
            'usd-correct' => ['USD', '123.45', false],
            'usd-incorrect' => ['USD', '123.456', true],
            'jpy-incorrect' => ['JPY', '123.45', true],
            'jpy-correct' => ['JPY', '123', false],
        ];
    }
}