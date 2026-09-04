<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Money as MoneyControl;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\Money as MoneyType;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Money as MoneyHelper;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Validation\Rules\MoneyRule;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money as MoneyLibrary;
use Override;

use function CraftCms\Cms\t;

/**
 * Money field type
 *
 * @property-read array{name:string,type:Type,description:string|null} $contentGqlMutationArgumentType
 * @property-read list<MoneyRule> $elementValidationRules
 * @property-read null|string $settingsHtml
 * @property-read null $elementConditionRuleType
 * @property-read mixed $contentGqlType
 */
class Money extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Money');
    }

    #[Override]
    public static function icon(): string
    {
        return self::currencyIcon(I18N::getLocale()->getDefaultCurrency());
    }

    private static function currencyIcon(string $currency): string
    {
        return match ($currency) {
            'CHF' => 'franc-sign',
            'EUR' => 'euro-sign',
            'GBP' => 'sterling-sign',
            'INR' => 'indian-rupee-sign',
            'JPY', 'CNY' => 'yen-sign',
            'KRW' => 'won-sign',
            'RUB' => 'ruble-sign',
            'TRY' => 'turkish-lira-sign',
            default => 'dollar-sign',
        };
    }

    #[Override]
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

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'defaultValue' => ['nullable', 'numeric'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric', 'gte:min'],
            'size' => ['nullable', 'integer'],
            'currency' => ['required', 'string', 'max:3'],
        ]);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $currencyOptions = [];
        foreach ($this->_isoCurrencies as $currency) {
            $currencyOptions[] = ['label' => $currency->getCode(), 'value' => $currency->getCode()];
        }

        return Form::make([
            FormField::make(t('Currency'))
                ->required()
                ->control(Choice::make('currency')->options($currencyOptions)->value($this->currency)),
            FormField::make(t('Default Value'))
                ->control(Number::make('defaultValue')->step('any')->value($this->decimalSetting($this->defaultValue))),
            FormField::make(t('Min Value'))
                ->control(Number::make('min')->step('any')->value($this->decimalSetting($this->min))),
            FormField::make(t('Max Value'))
                ->control(Number::make('max')->step('any')->value($this->decimalSetting($this->max))),
            FormField::make(t('Show Currency'))
                ->control(Lightswitch::make('showCurrency')->value($this->showCurrency)),
            FormField::make(t('Size'))
                ->control(Number::make('size')->min(1)->value($this->size)),
        ]);
    }

    private function decimalSetting(int|float|null $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $decimal = MoneyHelper::toDecimal(new MoneyLibrary($value, new Currency($this->currency)));

        return $decimal === false ? null : (float) $decimal;
    }

    #[Override]
    public function getIcon(): string
    {
        return self::currencyIcon($this->currency);
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_DECIMAL;
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): void
    {
        $valueSql = self::valueSql($instances);

        $query->whereMoneyParam($valueSql, $instances[0]->currency, $value);
    }

    public function getDefaultValue(): float|int|null
    {
        return $this->defaultValue;
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $value = $context->value instanceof MoneyLibrary
            ? MoneyHelper::toNumber($context->value)
            : $context->value;

        return MoneyControl::make($context->path)
            ->currency($this->currency)
            ->min($this->min)
            ->max($this->max)
            ->size($this->size)
            ->showCurrency($this->showCurrency)
            ->value([
                'value' => $value,
                'locale' => I18N::getFormattingLocale()->id,
            ]);
    }

    #[Override]
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

    #[Override]
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

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if ($value === null) {
            // Override the initial value being set to null by _includes/forms/field
            DeltaRegistry::setInitialValue($this->handle, [
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

        return FormFields::moneyInputHtml([
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

    #[Override]
    public function prepareForElementValidation(mixed $value): mixed
    {
        if ($value && ! $value instanceof MoneyLibrary) {
            $currency = ! $value['currency'] instanceof Currency ? new Currency($value['currency']) : $value['currency'];
            $value = new MoneyLibrary($value['value'], $currency);
        }

        return $value;
    }

    /** @return list<MoneyRule> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            new MoneyRule($element, $this->min, $this->max),
        ];
    }

    public function getElementConditionRuleType(): string
    {
        return MoneyFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return MoneyHelper::toString($value) ?: '';
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = new MoneyLibrary(1234, new Currency($this->currency));
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        return MoneyType::getType();
    }

    /** @return array{name:string,type:Type,description:string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => MoneyType::getType(),
            'description' => $this->instructions,
        ];
    }
}
