<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\NumberFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\DefaultableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Number as NumberControl;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\Number as NumberType;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Translation\Locale;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Override;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Number represents a Number field.
 */
class Number extends Field implements CrossSiteCopyableFieldInterface, DefaultableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    public const string FORMAT_DECIMAL = 'decimal';

    public const string FORMAT_CURRENCY = 'currency';

    public const string FORMAT_NONE = 'none';

    #[Override]
    public static function displayName(): string
    {
        return t('Number');
    }

    #[Override]
    public static function icon(): string
    {
        return 'input-numeric';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'int|float|null';
    }

    #[Override]
    public static function dbType(): string
    {
        if (DB::isMysql()) {
            return sprintf('%s(65,16)', Query::TYPE_DECIMAL);
        }

        return Query::TYPE_DECIMAL;
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        $valueSql = self::valueSql($instances);

        return $query->whereNumericParam($valueSql, $value, columnType: self::dbType());
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $currencies = [['label' => t('Choose a currency…'), 'value' => '']];
        foreach (new ISOCurrencies as $currency) {
            $currencies[] = ['label' => $currency->getCode(), 'value' => $currency->getCode()];
        }

        return Form::make([
            FormField::make(t('Min Value'), NumberControl::make('min')->step('any')->value($this->min)),
            FormField::make(t('Max Value'), NumberControl::make('max')->step('any')->value($this->max)),
            FormField::make(t('Step Size'), NumberControl::make('step')->step('any')->value($this->step)),
            FormField::make(t('Decimal Points'), NumberControl::make('decimals')->min(0)->value($this->decimals)),
            FormField::make(t('Size'), NumberControl::make('size')->min(1)->value($this->size)),
            FormField::make(t('Default Value'), NumberControl::make('defaultValue')->step('any')->value($this->defaultValue)),
            FormField::make(t('Prefix Text'))
                ->instructions(t('Text that should be shown before the input.'))
                ->control(Text::make('prefix')->value($this->prefix)),
            FormField::make(t('Suffix Text'))
                ->instructions(t('Text that should be shown after the input.'))
                ->control(Text::make('suffix')->value($this->suffix)),
            FormField::make(t('Preview Format'))
                ->instructions(t('How field values will be formatted within element indexes.'))
                ->control(Choice::make('previewFormat')->options([
                    ['label' => t('As decimal numbers'), 'value' => self::FORMAT_DECIMAL],
                    ['label' => t('As currency values'), 'value' => self::FORMAT_CURRENCY],
                    ['label' => t('Unformatted'), 'value' => self::FORMAT_NONE],
                ])->value($this->previewFormat)),
            FormField::make(t('Preview Currency'))
                ->control(Choice::make('previewCurrency')->options($currencies)->value($this->previewCurrency)),
        ]);
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return NumberControl::make($context->path)
            ->min($this->min)
            ->max($this->max)
            ->step($this->step)
            ->size($this->size)
            ->value($context->value);
    }

    /**
     * @var int|float|null The minimum allowed number
     */
    public int|null|float $min = 0;

    /**
     * @var int|float|null The maximum allowed number
     */
    public int|null|float $max = null;

    /**
     * @var int|float|null The step value for the input
     */
    public int|float|null $step = null;

    /**
     * @var int The number of digits allowed after the decimal point
     */
    public int $decimals = 0;

    /**
     * @var int|null The size of the field
     */
    public ?int $size = null;

    /**
     * @var int|float|null The default value for new elements
     */
    public int|null|float $defaultValue = null;

    /**
     * @var string|null Text that should be displayed before the input
     */
    public ?string $prefix = null;

    /**
     * @var string|null Text that should be displayed after the input
     */
    public ?string $suffix = null;

    /**
     * @var string How the number should be formatted in element index views.
     *
     * @phpstan-var self::FORMAT_DECIMAL|self::FORMAT_CURRENCY|self::FORMAT_NONE
     */
    public string $previewFormat = self::FORMAT_DECIMAL;

    /**
     * @var string|null The currency that should be used if [[$previewFormat]] is set to `currency`.
     */
    public ?string $previewCurrency = null;

    public function __construct($config = [])
    {
        // Config normalization
        foreach (['defaultValue', 'min', 'max', 'step'] as $name) {
            if (isset($config[$name])) {
                $config[$name] = $this->_normalizeNumber($config[$name]);
            }
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        $conditionalInteger = Rule::when(fn ($input) => ! $input->decimals, 'integer', 'numeric');

        return array_merge(parent::getRules(), [
            'min' => ['nullable', $conditionalInteger],
            'max' => ['nullable', $conditionalInteger, 'gte:min'],
            'step' => ['nullable', 'numeric'],
            'defaultValue' => ['nullable', $conditionalInteger],
            'decimals' => ['nullable', 'integer'],
            'size' => ['nullable', 'integer'],
            'previewFormat' => ['nullable', Rule::in([self::FORMAT_DECIMAL, self::FORMAT_CURRENCY, self::FORMAT_NONE])],
            'previewCurrency' => [
                'nullable',
                'required_if:previewFormat,'.self::FORMAT_CURRENCY,
                'string',
                'min:3',
                'max:3',
            ],
        ]);
    }

    public function getDefaultValue(): int|null|float
    {
        return $this->defaultValue;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null) {
            if (isset($this->defaultValue) && $this->isFresh($element)) {
                return $this->defaultValue;
            }

            return null;
        }

        return $this->_normalizeNumber($value);
    }

    private function _normalizeNumber(mixed $value): float|int|null
    {
        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = I18N::normalizeNumber($value['value'], $value['locale']);
        }

        if (is_int($value) || is_float($value) || (is_string($value) && $value !== '')) {
            $float = (float) $value;
            $int = (int) $float;

            return $int == $float ? $int : $float;
        }

        return null;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): int|null|float
    {
        if ($value === null) {
            return null;
        }

        // ensure we only store the selected number of decimals and that the result is the same as in v4
        // https://github.com/craftcms/cms/issues/16181
        $value = round((float) $value, $this->decimals);

        return $this->decimals === 0 ? (int) $value : $value;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $formatter = I18N::getFormatter();

        try {
            $formatNumber = ! $this->step && ! $formatter->willBeMisrepresented($value);
        } catch (InvalidArgumentException) {
            $formatNumber = false;
        }

        if ($formatNumber) {
            if ($value !== null) {
                if ($this->previewFormat !== self::FORMAT_NONE) {
                    try {
                        $value = $formatter->asDecimal($value, $this->decimals);
                    } catch (InvalidArgumentException) {
                    }
                } elseif ($this->decimals !== 0) {
                    // Just make sure we're using the right decimal symbol
                    $decimalSeparator = I18N::getFormattingLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
                    try {
                        $value = number_format($value, $this->decimals, $decimalSeparator, '');
                    } catch (Throwable) {
                        // NaN
                    }
                }
            } else {
                // Override the initial value being set to null by CustomField::inputHtml()
                DeltaRegistry::setInitialValue($this->handle, [
                    'locale' => I18N::getFormattingLocale()->id,
                    'value' => '',
                ]);
            }
        }

        $id = $this->getInputId();
        $namespacedId = InputNamespace::namespaceId($id);

        $js = <<<JS
(function() {
    const input = \$('#$namespacedId');
    input.on('input', () => {
        Craft.filterNumberInputVal(input);
    });
})();
JS;

        HtmlStack::js($js);

        return template('_components/fieldtypes/Number/input', [
            'id' => $id,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'value' => $value,
            'formatNumber' => $formatNumber,
            'currencyLabel' => $this->previewFormat === self::FORMAT_CURRENCY ? $this->currencyLabel() : false,
        ]);
    }

    private function currencyLabel(): string
    {
        return t('({currencyCode}) {currencySymbol}', [
            'currencyCode' => $this->previewCurrency,
            'currencySymbol' => I18N::getFormattingLocale()->getCurrencySymbol($this->previewCurrency),
        ]);
    }

    /** @return array<int,string> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return array_filter([
            'numeric',
            $this->min !== null ? "min:{$this->min}" : null,
            $this->max !== null ? "max:{$this->max}" : null,
        ]);
    }

    public function getElementConditionRuleType(): array|string
    {
        if ($this->decimals) {
            return [
                'class' => NumberFieldConditionRule::class,
                'step' => null,
            ];
        }

        return NumberFieldConditionRule::class;
    }

    #[Override]
    protected function dbTypeForValueSql(): string
    {
        if (! $this->decimals) {
            return Query::TYPE_INTEGER;
        }

        if (DB::isMysql()) {
            return sprintf('%s(65,%s)', Query::TYPE_DECIMAL, $this->decimals);
        }

        return Query::TYPE_DECIMAL;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = match ($this->previewFormat) {
            self::FORMAT_DECIMAL => I18N::getFormatter()->asDecimal($value, $this->decimals),
            self::FORMAT_CURRENCY => I18N::getFormatter()->asCurrency($value, $this->previewCurrency, ! $this->decimals),
            default => $value,
        };

        if ($this->prefix) {
            $formatted = Markdown::parseParagraph(Html::encode($this->prefix)).$formatted;
        }

        if ($this->suffix) {
            $formatted .= Markdown::parseParagraph(Html::encode($this->suffix));
        }

        return $formatted;
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            if (isset($this->min, $this->max)) {
                $min = $this->min * (10 ^ $this->decimals);
                $max = $this->max * (10 ^ $this->decimals);
                if ($this->step) {
                    $step = $this->step * (10 ^ $this->decimals);
                    $value = random_int($min / $step, $max / $step) * $step;
                } else {
                    $value = random_int($min, $max);
                }
                $value /= 10 ^ $this->decimals;
            } else {
                $value = 1234;
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        return NumberType::getType();
    }

    /** @return array{name:string,type:Type,description:string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
