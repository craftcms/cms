<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\gql\types\Number as NumberType;
use craft\helpers\Localization;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\NumberFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Translation\Locale;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Override;
use Throwable;
use yii\db\Schema;
use yii\helpers\Markdown;

use function CraftCms\Cms\t;

/**
 * Number represents a Number field.
 */
final class Number extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
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

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
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
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
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
        $namespacedId = InputNamespace::namespaceInputId($id);

        $js = <<<JS
(function() {
    const input = \$('#$namespacedId');
    input.on('input', () => {
        Craft.filterNumberInputVal(input);
    });
})();
JS;

        AssetRegistry::js($js);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/input.twig', [
            'id' => $id,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'value' => $value,
            'formatNumber' => $formatNumber,
        ]);
    }

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
            return Schema::TYPE_INTEGER;
        }

        if (Craft::$app->getDb()->getIsMysql()) {
            return sprintf('%s(65,%s)', Schema::TYPE_DECIMAL, $this->decimals);
        }

        return Schema::TYPE_DECIMAL;
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
            $formatted = Markdown::processParagraph(Html::encode($this->prefix)).$formatted;
        }

        if ($this->suffix) {
            $formatted .= Markdown::processParagraph(Html::encode($this->suffix));
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
