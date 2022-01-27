<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\gql\types\Number as NumberType;
use craft\helpers\Html;
use craft\helpers\Localization;
use craft\helpers\Number as NumberHelper;
use craft\validators\MoneyValidator;
use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money as MoneyLibrary;
use Money\Parser\DecimalMoneyParser;
use NumberFormatter;
use yii\db\Schema;

/**
 * Money represents a Money field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 *
 * @property-read array $contentGqlMutationArgumentType
 * @property-read array[] $elementValidationRules
 * @property-read string[] $contentColumnType
 * @property-read null|Currency $currency
 * @property-read null|string $settingsHtml
 * @property-read null $elementConditionRuleType
 * @property-read mixed $contentGqlType
 * @property-read Currencies $currencies
 */
class Money extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Money');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return MoneyLibrary::class . '|null';
    }

    /**
     * @var string The default currency
     */
    public string $defaultCurrency = 'USD';

    /**
     * @var int|float|null The default value for new elements
     */
    public $defaultValue;

    /**
     * @var int|float|null The minimum allowed number
     */
    public $min = 0;

    /**
     * @var int|float|null The maximum allowed number
     */
    public $max;

    /**
     * @var int|null The size of the field
     */
    public ?int $size = null;

    /**
     * @var string[] The allowed list of currencies available for the input
     */
    public array $allowedCurrencies = [];

    /**
     * @var ISOCurrencies
     */
    private ISOCurrencies $_isoCurrencies;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name])) {
                $config[$name] = $this->_normalizeNumber($config[$name]);
            }
        }

        foreach (['defaultValue', 'max', 'size'] as $name) {
            if (($config[$name] ?? null) === '') {
                unset($config[$name]);
            }
        }

        if (($config['min'] ?? null) === '') {
            $config['min'] = null; // default is 0
        }

        foreach (['min', 'max', 'defaultValue'] as $name) {
            if (isset($config[$name])) {
                $config[$name] = NumberHelper::toIntOrFloat($config[$name]);
            }
        }

        if (!isset($this->_isoCurrencies)) {
            $this->_isoCurrencies = new ISOCurrencies();
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['defaultCurrency'], 'required'];
        $rules[] = [['defaultCurrency'], 'string', 'max' => 3];
        $rules[] = [['size'], 'integer'];
        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>=',
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Money/settings',
            [
                'field' => $this,
                'currencies' => $this->_isoCurrencies
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): array
    {
        return [
            'money' => Schema::TYPE_STRING . '(255)',
            'currency' => Schema::TYPE_STRING . '(5)',
        ];
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return new Currency('USD');
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ?ElementInterface $element = null)
    {
        if ($value instanceof MoneyLibrary) {
            return $value;
        }

        if ($value === null) {
            if (isset($this->defaultValue) && $this->isFresh($element)) {
                return $this->defaultValue;
            }
            return null;
        }

        if (!$value['currency'] instanceof Currency) {
            $value['currency'] = new Currency($value['currency'] ?? $this->defaultCurrency);
        }

        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['money'])) {
            $value['money'] = Localization::normalizeNumber($value['money'], $value['locale']);
            $currencies = $this->_isoCurrencies;
            return (new DecimalMoneyParser($currencies))
                ->parse((string)$value['money'], $value['currency']);
        }

        return new MoneyLibrary($value['money'], $value['currency']);
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return array|null
     */
    public function serializeValue($value, ElementInterface $element = null): ?array
    {
        if (!$value) {
            return null;
        }

        /** @var MoneyLibrary $value */
        return [
            'money' => $value->getAmount(),
            'currency' => $value->getCurrency()->getCode(),
        ];
    }

    /**
     * @param mixed $value
     * @return int|float|string|null
     */
    private function _normalizeNumber($value)
    {
        if ($value === '') {
            return null;
        }

        if (is_string($value) && is_numeric($value)) {
            if ((int)$value == $value) {
                return (int)$value;
            }
            if ((float)$value == $value) {
                return (float)$value;
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ?ElementInterface $element = null): string
    {
        $id = Html::id($this->handle);
        $view = Craft::$app->getView();
        $namespacedId = $view->namespaceInputId($id);

        $js = <<<JS
(function() {
    \$('#$namespacedId').on('keydown', ev => {
        if (
            !Garnish.isCtrlKeyPressed(ev) &&
            ![
                9, // tab,
                13, // return / enter
                27, // esc
                8, 46, // backspace, delete
                37, 38, 39, 40, // arrows
                173, 189, 109, // minus, subtract
                190, 110, // period, decimal
                188, // comma
                48, 49, 50, 51, 52, 53, 54, 55, 56, 57, // 0-9
                96, 97, 98, 99, 100, 101, 102, 103, 104, 105, // numpad 0-9
            ].includes(ev.which)
        ) {
            ev.preventDefault();
        }
    });
})();
JS;

        $view->registerJs($js);

        $currencyCode = $this->defaultCurrency;


        if ($value instanceof MoneyLibrary) {
            $currenciesOptions = $this->_getCurrenciesOptionsList($value);
            $moneyFormatter = new DecimalMoneyFormatter($this->_isoCurrencies);
            $currencyCode = $value->getCurrency()->getCode();

            $value = Craft::$app->getFormatter()->asDecimal($moneyFormatter->format($value), $this->_isoCurrencies->subunitFor($value->getCurrency()));
        } else {
            $currenciesOptions = $this->_getCurrenciesOptionsList();
        }

        return $view->renderTemplate('_components/fieldtypes/Money/input', [
            'id' => $id,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'currency' => $currencyCode,
            'value' => $value,
            'currencies' => $currenciesOptions,
            'fieldContainer' => $namespacedId . '-field',
        ]);
    }

    /**
     * @param MoneyLibrary|null $value
     * @return array
     */
    private function _getCurrenciesOptionsList(?MoneyLibrary $value = null): array
    {
        $currencies = $this->allowedCurrencies;

        if (empty($currencies)) {
            foreach ($this->_isoCurrencies as $isoCurrency) {
                $currencies[] = $isoCurrency->getCode();
            }
        }

        $currenciesOptionList = [];
        foreach ($currencies as $currency) {
            $currenciesOptionList[] = [
                'label' => Craft::t('app', '({currencyCode}) {currencySymbol}', [
                    'currencyCode' => $currency,
                    'currencySymbol' => Craft::$app->getFormattingLocale()->getCurrencySymbol($currency),
                ]),
                'value' => $currency
            ];
        }

        // Add the current selected currency if it is no longer in the allowed currencies list
        if ($value instanceof MoneyLibrary && !in_array($value->getCurrency()->getCode(), $currencies, true)) {
            $currencyCode = $value->getCurrency()->getCode();
            $currenciesOptionList[] = ['optgroup' => Craft::t('app', 'Currencies not available')];
            $currenciesOptionList[] = [
                'label' => Craft::t('app', '({currencyCode}) {currencySymbol}', [
                    'currencyCode' => $currencyCode,
                    'currencySymbol' => Craft::$app->getFormattingLocale()->getCurrencySymbol($currencyCode),
                ]),
                'value' => $currencyCode,
                'disabled' => true,
            ];
        }

        return $currenciesOptionList;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [MoneyValidator::class, 'allowedCurrencies' => $this->allowedCurrencies],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value instanceof MoneyLibrary) {
            return '';
        }

        $numberFormatter = new NumberFormatter(Craft::$app->getFormattingLocale()->id, NumberFormatter::CURRENCY);
        return (new IntlMoneyFormatter($numberFormatter, $this->_isoCurrencies))->format($value);
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType()
    {
        return NumberType::getType();
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType()
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
