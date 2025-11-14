<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\Entry;
use craft\fields\conditions\MoneyFieldConditionRule;
use craft\gql\types\Money as MoneyType;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\MoneyHelper;
use craft\validators\MoneyValidator;
use GraphQL\Type\Definition\Type;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money as MoneyLibrary;
use yii\db\Schema;

/**
 * Money field type
 *
 * @property-read array $contentGqlMutationArgumentType
 * @property-read array[] $elementValidationRules
 * @property-read null|string $settingsHtml
 * @property-read null $elementConditionRuleType
 * @property-read mixed $contentGqlType
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Money extends Field implements InlineEditableFieldInterface, SortableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
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
    public static function icon(): string
    {
        return 'dollar-sign';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s', MoneyLibrary::class);
    }

    /**
     * @var non-empty-string The default currency
     */
    public string $currency = 'USD';

    /**
     * @var int|float|null The default value for new elements
     */
    public int|null|float $defaultValue = null;

    /**
     * @var int|float|null The minimum allowed number
     */
    public int|null|float $min = 0;

    /**
     * @var int|float|null The maximum allowed number
     */
    public int|null|float $max = null;

    /**
     * @var bool Whether to show the currency label.
     */
    public bool $showCurrency = true;

    /**
     * @var int|null The size of the field
     */
    public ?int $size = null;

    /**
     * @var ISOCurrencies
     */
    private ISOCurrencies $_isoCurrencies;

    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        $this->_isoCurrencies = new ISOCurrencies();

        // Config normalization
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name])) {
                // at this point the currency property isn't set yet, so we need to explicitly pass it to the _normalizeNumber()
                // see https://github.com/craftcms/cms/issues/15565 for more details
                $config[$name] = $this->_normalizeNumber($config[$name], $config['currency'] ?? null);
            }
        }

        if (isset($config['size']) && !is_numeric($config['size'])) {
            $config['size'] = null;
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
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        foreach (['defaultValue', 'min', 'max'] as $attr) {
            if ($this->$attr !== null) {
                $value = MoneyHelper::toDecimal(new MoneyLibrary($this->$attr, new Currency($this->currency)));
                $this->$attr = $value !== false ? (float)$value : null;
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Money/settings.twig', [
            'field' => $this,
            'currencies' => $this->_isoCurrencies,
            'subUnits' => $this->subunits(),
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_DECIMAL;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $valueSql = static::valueSql($instances);
        return Db::parseMoneyParam($valueSql, $instances[0]->currency, $value);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof MoneyLibrary) {
            return $value;
        }

        if ($value === null) {
            if (isset($this->defaultValue) && $this->isFresh($element)) {
                $value = $this->defaultValue;
            } else {
                // Allow a `null` value
                return null;
            }
        }

        if (is_array($value)) {
            if (!isset($value['value']) || $value['value'] === '') {
                return null;
            }

            $value += [
                'locale' => Craft::$app->getFormattingLocale()->id,
                'currency' => $this->currency,
            ];

            return MoneyHelper::toMoney($value);
        }

        // If it's not a string, bail
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }

        // Fail-safe if the value is not in the correct format
        // Try to normalize the value if there are any non-numeric characters (except minus sign at the start)
        if (is_string($value) && !preg_match('/^(-?)\d+$/', $value)) {
            try {
                $value = MoneyHelper::normalizeString($value, new Currency($this->currency));
            } catch (ParserException) {
                // Catch a parse and return appropriately
                if (isset($this->defaultValue) && $this->isFresh($element)) {
                    $value = $this->defaultValue;
                } else {
                    // Allow a `null` value
                    return null;
                }
            }
        }

        return new MoneyLibrary($value, new Currency($this->currency));
    }

    /**
     * @param mixed $value
     * @param ElementInterface|null $element
     * @return string|null
     */
    public function serializeValue(mixed $value, ElementInterface $element = null): ?string
    {
        if (!$value) {
            return null;
        }

        /** @var MoneyLibrary $value */
        return $value->getAmount();
    }

    /**
     * @param mixed $value
     * @param string|null $currency
     * @return string|null
     */
    private function _normalizeNumber(mixed $value, ?string $currency = null): ?string
    {
        if ($value === '') {
            return null;
        }

        $currency ??= $this->currency;

        // Was this submitted with a locale ID? (This means the data is coming from the settings form)
        if (isset($value['locale'], $value['value'])) {
            if ($value['value'] === '') {
                return null;
            }

            $value['currency'] = $currency;
            $money = MoneyHelper::toMoney($value);
            return $money ? $money->getAmount() : null;
        }

        $money = new MoneyLibrary($value, new Currency($currency));
        return $money->getAmount();
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();

        if ($value === null) {
            // Override the initial value being set to null by _includes/forms/field
            $view->setInitialDeltaValue($this->handle, [
                'locale' => Craft::$app->getFormattingLocale()->id,
                'value' => '',
            ]);
        }

        $decimals = null;

        if ($value instanceof MoneyLibrary) {
            $decimals = $this->subunits($value->getCurrency());
            $value = MoneyHelper::toNumber($value);
        }

        $decimals ??= $this->subunits();

        $defaultValue = null;
        if (isset($this->defaultValue)) {
            $defaultValue = MoneyHelper::toNumber(new MoneyLibrary($this->defaultValue, new Currency($this->currency)));
        }

        return Cp::moneyInputHtml([
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'size' => $this->size,
            'currency' => $this->currency,
            'currencyLabel' => $this->currencyLabel(),
            'showCurrency' => $this->showCurrency,
            'decimals' => $decimals,
            'defaultValue' => $defaultValue,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'value' => $value,
        ]);
    }

    /**
     * @return string
     * @since 5.0.0
     */
    public function currencyLabel(): string
    {
        return Craft::t('app', '({currencyCode}) {currencySymbol}', [
            'currencyCode' => $this->currency,
            'currencySymbol' => Craft::$app->getFormattingLocale()->getCurrencySymbol($this->currency),
        ]);
    }

    /**
     * @param Currency|null $currency
     * @return int
     * @since 5.0.0
     */
    public function subunits(?Currency $currency = null): int
    {
        $currency ??= new Currency($this->currency);
        return $this->_isoCurrencies->subunitFor($currency);
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
    public function getElementConditionRuleType(): array|string|null
    {
        return MoneyFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return MoneyHelper::toString($value) ?: '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = new MoneyLibrary(1234, new Currency($this->currency));
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return MoneyType::getType();
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => MoneyType::getType(),
            'description' => $this->instructions,
        ];
    }
}
