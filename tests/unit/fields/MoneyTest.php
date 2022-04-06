<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Codeception\Test\Unit;
use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\fields\Money;
use craft\test\TestCase;
use Money\Currency;

/**
 * Unit tests for the Money custom field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MoneyTest extends TestCase
{
    /**
     * @var Money
     */
    public Money $field;

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        $this->field = new Money();
    }

    /**
     * @dataProvider constructorDataProvider
     * @param array $config
     * @param array $expected
     * @return void
     */
    public function testConstruct(array $config, array $expected): void
    {
        $moneyField = new Money($config);

        foreach ($expected as $attr => $value) {
            self::assertEquals($value, $moneyField->$attr);
        }
    }

    /**
     * @dataProvider normalizeValueDataProvider
     * @param mixed $money
     * @param string $value
     * @param string|null $defaultValue
     * @param ElementInterface|null $element
     */
    public function testNormalizeValue(mixed $money, string $value, ?string $defaultValue, ?ElementInterface $element): void
    {
        $this->field->defaultValue = $defaultValue !== null ? (float)$defaultValue : null;
        $normalized = $this->field->normalizeValue($money, $element);

        self::assertInstanceOf(\Money\Money::class, $normalized);
        self::assertEquals($value, $normalized->getAmount());
    }

    /**
     * @dataProvider getTableAttributeHtmlDataProvider
     * @param mixed $value
     * @param string $expected
     * @param string|null $locale
     * @return void
     */
    public function testGetTableAttributeHtml(mixed $value, string $expected, ?string $locale = null): void
    {
        if ($locale) {
            $oldLocaleId = Craft::$app->getFormattingLocale()->id;
            Craft::$app->getFormattingLocale()->id = $locale;
        }

        $html = $this->field->getTableAttributeHtml($value, new Entry());

        self::assertIsString($html);
        self::assertEquals($expected, $html);

        if ($locale) {
            Craft::$app->getFormattingLocale()->id = $oldLocaleId;
        }
    }

    /**
     * @dataProvider serializeValueDataProvider
     * @param \Money\Money|null $value
     * @param string|null $expected
     * @return void
     */
    public function testSerializeValue(?\Money\Money $value, ?string $expected): void
    {
        $serialized = $this->field->serializeValue($value);

        if ($value instanceof \Money\Money) {
            self::assertIsString($serialized);
        } else {
            self::assertNull($serialized);
        }
        self::assertEquals($expected, $serialized);
    }

    /**
     * @return array[]
     */
    public function constructorDataProvider(): array
    {
        return [
            [
                [
                    'currency' => 'USD',
                    'defaultValue' => '123',
                    'min' => '100',
                    'max' => '456',
                ],
                [
                    'currency' => 'USD',
                    'defaultValue' => '123',
                    'min' => '100',
                    'max' => '456',
                ],
            ],
            [
                [
                    'currency' => 'USD',
                    'defaultValue' => ['locale' => 'nl', 'value' => '1.234,56'],
                    'min' => ['locale' => 'nl', 'value' => '100'],
                    'max' => ['locale' => 'nl', 'value' => '5000,00'],
                ],
                [
                    'currency' => 'USD',
                    'defaultValue' => '123456',
                    'min' => '10000',
                    'max' => '500000',
                ],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function normalizeValueDataProvider(): array
    {
        $freshEntry = new Entry();
        $freshEntry->setIsFresh(true);

        return [
            'money-object' => [new \Money\Money(100, new Currency('USD')), '100', null, null],
            'default-value' => [null, '123', '123', $freshEntry],
            'array-passed' => [
                [
                    'value' => '1,23',
                    'locale' => 'nl',
                ], '123', null, null,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getTableAttributeHtmlDataProvider(): array
    {
        return [
            [new \Money\Money('100', new Currency('USD')), '$1.00', null],
            ['$1.00', '$1.00', null],
            [new \Money\Money('100', new Currency('USD')), "US$\xc2\xa01,00", 'nl'],
        ];
    }

    /**
     * @return array[]
     */
    public function serializeValueDataProvider(): array
    {
        return [
            [null, null],
            [new \Money\Money('100', new Currency('USD')), '100'],
        ];
    }
}
