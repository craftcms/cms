<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\fields\conditions\MoneyFieldConditionRule;
use craft\gql\types\Money as MoneyType;
use craft\helpers\Cp;
use craft\validators\MoneyValidator;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Money as MoneyHelper;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Query\Builder;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money as MoneyLibrary;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Money field type
 *
 * @property-read array $contentGqlMutationArgumentType
 * @property-read array[] $elementValidationRules
 * @property-read null|string $settingsHtml
 * @property-read null $elementConditionRuleType
 * @property-read mixed $contentGqlType
 */
final class Money extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Money');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'dollar-sign';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    private readonly ISOCurrencies $_isoCurrencies;

    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        $this->_isoCurrencies = new ISOCurrencies;

        // Config normalization
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name])) {
                // at this point the currency property isn't set yet, so we need to explicitly pass it to the _normalizeNumber()
                // see https://github.com/craftcms/cms/issues/15565 for more details
                $config[$name] = $this->_normalizeNumber($config[$name], $config['currency'] ?? null);
            }
        }

        if (isset($config['size']) && ! is_numeric($config['size'])) {
            $config['size'] = null;
        }

        parent::__construct($config);
    }

    #[\Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'defaultValue' => ['nullable', 'numeric'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric', 'gte:min'],
            'size' => ['nullable', 'integer'],
            'currency' => ['required', 'string', 'max:3'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        foreach (['defaultValue', 'min', 'max'] as $attr) {
            if ($this->$attr !== null) {
                $value = MoneyHelper::toDecimal(new MoneyLibrary($this->$attr, new Currency($this->currency)));
                $this->$attr = $value !== false ? (float) $value : null;
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
     * {@inheritdoc}
     */
    #[\Override]
    public static function dbType(): string
    {
        return Schema::TYPE_DECIMAL;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        $valueSql = self::valueSql($instances);

        return $query->whereMoneyParam($valueSql, $instances[0]->currency, $value);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): MoneyLibrary|null|false
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
            if (! isset($value['value']) || $value['value'] === '') {
                return null;
            }

            $value += [
                'locale' => I18N::getFormattingLocale()->id,
                'currency' => $this->currency,
            ];

            return MoneyHelper::toMoney($value);
        }

        // If it's not a string, bail
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        // Fail-safe if the value is not in the correct format
        // Try to normalize the value if there are any non-numeric characters (except minus sign at the start)
        if (is_string($value) && ! preg_match('/^(-?)\d+$/', $value)) {
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

        if (is_float($value)) {
            $value = (int) $value;
        }

        return new MoneyLibrary($value, new Currency($this->currency));
    }

    #[\Override]
    public function serializeValue(mixed $value, ?ElementInterface $element = null): ?string
    {
        if (! $value) {
            return null;
        }

        /** @var MoneyLibrary $value */
        return $value->getAmount();
    }

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
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();

        if ($value === null) {
            // Override the initial value being set to null by _includes/forms/field
            $view->setInitialDeltaValue($this->handle, [
                'locale' => I18N::getFormattingLocale()->id,
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

    public function currencyLabel(): string
    {
        return t('({currencyCode}) {currencySymbol}', [
            'currencyCode' => $this->currency,
            'currencySymbol' => I18N::getFormattingLocale()->getCurrencySymbol($this->currency),
        ]);
    }

    public function subunits(?Currency $currency = null): int
    {
        $currency ??= new Currency($this->currency);

        return $this->_isoCurrencies->subunitFor($currency);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getElementValidationRules(): array
    {
        return [
            [MoneyValidator::class, 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return MoneyFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return MoneyHelper::toString($value) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = new MoneyLibrary(1234, new Currency($this->currency));
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): Type
    {
        return MoneyType::getType();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => MoneyType::getType(),
            'description' => $this->instructions,
        ];
    }
}
