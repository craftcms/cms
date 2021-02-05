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
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Localization;
use craft\i18n\Locale;

/**
 * Number represents a Number field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Number extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @since 3.5.11
     */
    const FORMAT_DECIMAL = 'decimal';
    /**
     * @since 3.5.11
     */
    const FORMAT_CURRENCY = 'currency';
    /**
     * @since 3.5.11
     */
    const FORMAT_NONE = 'none';

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
    public static function valueType(): string
    {
        return 'int|float|null';
    }

    /**
     * @var int|float|null The default value for new elements
     */
    public $defaultValue;

    /**
     * @var int|float The minimum allowed number
     */
    public $min = 0;

    /**
     * @var int|float|null The maximum allowed number
     */
    public $max;

    /**
     * @var int The number of digits allowed after the decimal point
     */
    public $decimals = 0;

    /**
     * @var int|null The size of the field
     */
    public $size;

    /**
     * @var string|null Text that should be displayed before the input
     */
    public $prefix;

    /**
     * @var string|null Text that should be displayed after the input
     */
    public $suffix;

    /**
     * @var string How the number should be formatted in element index views.
     * @since 3.5.11
     */
    public $previewFormat = self::FORMAT_DECIMAL;

    /**
     * @var string|null The currency that should be used if [[$previewFormat]] is set to `currency`.
     * @since 3.5.11
     */
    public $previewCurrency;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function __construct($config = [])
    {
        // Normalize number settings
        foreach (['defaultValue', 'min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Normalize $defaultValue
        if ($this->defaultValue === '') {
            $this->defaultValue = null;
        }

        // Normalize $max
        if ($this->max === '') {
            $this->max = null;
        }

        // Normalize $min
        if ($this->min === '') {
            $this->min = null;
        }

        // Normalize $decimals
        if (!$this->decimals) {
            $this->decimals = 0;
        }

        // Normalize $size
        if ($this->size !== null && !$this->size) {
            $this->size = null;
        }

        if ($this->prefix === '') {
            $this->prefix = null;
        }

        if ($this->suffix === '') {
            $this->suffix = null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['defaultValue', 'min', 'max'], 'number'];
        $rules[] = [['decimals', 'size'], 'integer'];
        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>='
        ];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        $rules[] = [['previewFormat'], 'in', 'range' => [self::FORMAT_DECIMAL, self::FORMAT_CURRENCY, self::FORMAT_NONE]];
        $rules[] = [
            ['previewCurrency'], 'required', 'when' => function(): bool {
                return $this->previewFormat === self::FORMAT_CURRENCY;
            }
        ];
        $rules[] = [['previewCurrency'], 'string', 'min' => 3, 'max' => 3, 'encoding' => '8bit'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/settings',
            [
                'field' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Db::getNumericalColumnType($this->min, $this->max, $this->decimals);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value === null) {
            if ($this->defaultValue !== null && $this->isFresh($element)) {
                return $this->defaultValue;
            }
            return null;
        }

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
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        if ($value !== null) {
            if ($this->previewFormat !== self::FORMAT_NONE) {
                $value = Craft::$app->getFormatter()->asDecimal($value, $this->decimals);
            } else if ($this->decimals) {
                // Just make sure we're using the right decimal symbol
                $decimalSeparator = Craft::$app->getFormattingLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
                try {
                    $value = number_format($value, $this->decimals, $decimalSeparator, '');
                } catch (\Throwable $e) {
                    // NaN
                }
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/input', [
            'id' => Html::id($this->handle),
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
            ['number', 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value === null) {
            return '';
        }

        switch ($this->previewFormat) {
            case self::FORMAT_DECIMAL:
                return Craft::$app->getFormatter()->asDecimal($value, $this->decimals);
            case self::FORMAT_CURRENCY:
                return Craft::$app->getFormatter()->asCurrency($value, $this->previewCurrency, [], [], !$this->decimals);
            default:
                return $value;
        }
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
     * @since 3.5.0
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
