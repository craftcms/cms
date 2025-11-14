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
use craft\fields\conditions\NumberFieldConditionRule;
use craft\gql\types\Number as NumberType;
use craft\helpers\Db;
use craft\helpers\Localization;
use craft\i18n\Locale;
use GraphQL\Type\Definition\Type;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\db\Schema;

/**
 * Number represents a Number field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Number extends Field implements InlineEditableFieldInterface, SortableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @since 3.5.11
     */
    public const FORMAT_DECIMAL = 'decimal';
    /**
     * @since 3.5.11
     */
    public const FORMAT_CURRENCY = 'currency';
    /**
     * @since 3.5.11
     */
    public const FORMAT_NONE = 'none';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Number');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'input-numeric';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'int|float|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        if (Craft::$app->getDb()->getIsMysql()) {
            return sprintf('%s(65,16)', Schema::TYPE_DECIMAL);
        }

        return Schema::TYPE_DECIMAL;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): ?array
    {
        $valueSql = static::valueSql($instances);
        return Db::parseNumericParam($valueSql, $value, columnType: static::dbType());
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
     * @since 5.5.0
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
     * @phpstan-var self::FORMAT_DECIMAL|self::FORMAT_CURRENCY|self::FORMAT_NONE
     * @since 3.5.11
     */
    public string $previewFormat = self::FORMAT_DECIMAL;

    /**
     * @var string|null The currency that should be used if [[$previewFormat]] is set to `currency`.
     * @since 3.5.11
     */
    public ?string $previewCurrency = null;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
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

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['min', 'max', 'step', 'defaultValue'], 'number'];
        $rules[] = [['decimals', 'size'], 'integer'];

        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>=',
        ];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        $rules[] = [['previewFormat'], 'in', 'range' => [self::FORMAT_DECIMAL, self::FORMAT_CURRENCY, self::FORMAT_NONE]];
        $rules[] = [
            ['previewCurrency'], 'required', 'when' => fn(): bool => $this->previewFormat === self::FORMAT_CURRENCY,
        ];
        $rules[] = [['previewCurrency'], 'string', 'min' => 3, 'max' => 3, 'encoding' => '8bit'];

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
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @param mixed $value
     * @return int|float|null
     */
    private function _normalizeNumber(mixed $value): float|int|null
    {
        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
        }

        if (is_int($value) || is_float($value) || (is_string($value) && $value !== '')) {
            $float = (float)$value;
            $int = (int)$float;
            return $int == $float ? $int : $float;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value === null) {
            return null;
        }

        // ensure we only store the selected number of decimals and that the result is the same as in v4
        // https://github.com/craftcms/cms/issues/16181
        $value = round((float)$value, $this->decimals);
        return $this->decimals === 0 ? (int)$value : $value;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();
        $formatter = Craft::$app->getFormatter();

        try {
            $formatNumber = !$this->step && !$formatter->willBeMisrepresented($value);
        } catch (InvalidArgumentException $e) {
            $formatNumber = false;
        }

        if ($formatNumber) {
            if ($value !== null) {
                if ($this->previewFormat !== self::FORMAT_NONE) {
                    try {
                        $value = Craft::$app->getFormatter()->asDecimal($value, $this->decimals);
                    } catch (InvalidArgumentException) {
                    }
                } elseif ($this->decimals !== 0) {
                    // Just make sure we're using the right decimal symbol
                    $decimalSeparator = Craft::$app->getFormattingLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
                    try {
                        $value = number_format($value, $this->decimals, $decimalSeparator, '');
                    } catch (Throwable) {
                        // NaN
                    }
                }
            } else {
                // Override the initial value being set to null by CustomField::inputHtml()
                $view->setInitialDeltaValue($this->handle, [
                    'locale' => Craft::$app->getFormattingLocale()->id,
                    'value' => '',
                ]);
            }
        }

        $id = $this->getInputId();
        $namespacedId = $view->namespaceInputId($id);

        $js = <<<JS
(function() {
    const input = \$('#$namespacedId');
    input.on('input', () => {
        Craft.filterNumberInputVal(input);
    });
})();
JS;

        $view->registerJs($js);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/input.twig', [
            'id' => $id,
            'describedBy' => $this->describedBy,
            'field' => $this,
            'value' => $value,
            'formatNumber' => $formatNumber,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            ['number', 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        if ($this->decimals) {
            return [
                'class' => NumberFieldConditionRule::class,
                'step' => null,
            ];
        }

        return NumberFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    protected function dbTypeForValueSql(): array|string|null
    {
        if (!$this->decimals) {
            return Schema::TYPE_INTEGER;
        }

        if (Craft::$app->getDb()->getIsMysql()) {
            return sprintf('%s(65,%s)', Schema::TYPE_DECIMAL, $this->decimals);
        }

        return Schema::TYPE_DECIMAL;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = match ($this->previewFormat) {
            self::FORMAT_DECIMAL => Craft::$app->getFormatter()->asDecimal($value, $this->decimals),
            self::FORMAT_CURRENCY => Craft::$app->getFormatter()->asCurrency($value, $this->previewCurrency, [], [], !$this->decimals),
            default => $value,
        };

        if ($this->prefix) {
            $formatted = $this->prefix . $formatted;
        }

        if ($this->suffix) {
            $formatted = $formatted . $this->suffix;
        }

        return $formatted;
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            if (isset($this->min, $this->max)) {
                $min = $this->min * (10 ^ $this->decimals);
                $max = $this->max * (10 ^ $this->decimals);
                if ($this->step) {
                    $step = $this->step * (10 ^ $this->decimals);
                    $value = mt_rand($min / $step, $max / $step) * $step;
                } else {
                    $value = mt_rand($min, $max);
                }
                $value /= 10 ^ $this->decimals;
            } else {
                $value = 1234;
            }
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return NumberType::getType();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => NumberType::getType(),
            'description' => $this->instructions,
        ];
    }
}
