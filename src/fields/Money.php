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
use Money\Currencies\ISOCurrencies;
use Money\Currency;
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
 * @property-read null|string $settingsHtml
 * @property-read null $elementConditionRuleType
 * @property-read mixed $contentGqlType
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
    public string $currency = 'USD';

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
        $rules[] = [['defaultValue', 'min', 'max'], 'validateSubUnits', 'skipOnEmpty' => true];
        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['currency'], 'required'];
        $rules[] = [['currency'], 'string', 'max' => 3];
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
     * @param string $attribute
     * @param array|null $params
     * @return void
     */
    public function validateSubUnits(string $attribute, ?array $params = null): void
    {
        $subUnits = $this->_isoCurrencies->subunitFor(new Currency($this->currency));
        // Check the number of decimal places in $this->$attribute
        if (strlen(substr(strrchr($this->$attribute, '.'), 1)) > $subUnits) {
            $this->addError($attribute, Craft::t('app', '{attribute} must be {number} decimal places.', [
                'attribute' => $attribute,
                'number' => $subUnits
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Money/settings',
            [
                'field' => $this,
                'currencies' => $this->_isoCurrencies,
                'subUnits' => $this->_isoCurrencies->subunitFor(new Currency($this->currency)),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING . '(1020)';
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ?ElementInterface $element = null)
    {
        if ($value instanceof MoneyLibrary) {
            return $value;
        }

        $locale = Craft::$app->getFormattingLocale()->id;

        if ($value === null && isset($this->defaultValue) && $this->isFresh($element)) {
            $value = $this->defaultValue;
        }

        if (is_array($value)) {
            $locale = $value['locale'] ?? $locale;
            $value = $value['money'] ?? null;
        }
        // Was this submitted with a locale ID?
        $value = Localization::normalizeNumber($value, $locale);

        return (new DecimalMoneyParser($this->_isoCurrencies))
            ->parse((string)$value, $this->currency);
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return string|null
     */
    public function serializeValue($value, ElementInterface $element = null): ?string
    {
        if (!$value) {
            return null;
        }

        /** @var MoneyLibrary $value */
        return $value->getAmount();
    }

    /**
     * @param mixed $value
     * @return int|float|string|null
     */
    private function _normalizeNumber($value)
    {
        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
        }

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

        $decimals = null;

        $numberFormatter = new NumberFormatter(Craft::$app->getFormattingLocale()->id, NumberFormatter::DECIMAL);
        if ($value instanceof MoneyLibrary) {
            $decimals = $this->_isoCurrencies->subunitFor($value->getCurrency());
            $value = (new IntlMoneyFormatter($numberFormatter, $this->_isoCurrencies))->format($value);
        }

        if ($decimals === null) {
            $decimals = $this->_isoCurrencies->subunitFor(new Currency($this->currency));
        }

        $defaultValue = null;
        if (isset($this->defaultValue)) {
            $defaultValue = $this->normalizeValue($this->defaultValue, $element);
            $defaultValue = (new IntlMoneyFormatter($numberFormatter, $this->_isoCurrencies))->format($defaultValue);
        }

        $currencyLabel = Craft::t('app', '({currencyCode}) {currencySymbol}', [
            'currencyCode' => $this->currency,
            'currencySymbol' => Craft::$app->getFormattingLocale()->getCurrencySymbol($this->currency),
        ]);

        return $view->renderTemplate('_components/fieldtypes/Money/input', [
            'id' => $id,
            'currency' => $this->currency,
            'currencyLabel' => $currencyLabel,
            'decimals' => $decimals,
            'defaultValue' => $defaultValue,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [MoneyValidator::class, 'min' => $this->min, 'max' => $this->max],
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
