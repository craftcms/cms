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
use craft\helpers\Localization;
use craft\i18n\Locale;
use yii\base\InvalidArgumentException;

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
            if (isset($config[$name])) {
                $config[$name] = $this->_normalizeNumber($config[$name]);
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
            'operator' => '>=',
        ];

        if (!$this->decimals) {
            $rules[] = [['defaultValue', 'min', 'max'], 'integer'];
        }

        $rules[] = [['previewFormat'], 'in', 'range' => [self::FORMAT_DECIMAL, self::FORMAT_CURRENCY, self::FORMAT_NONE]];
        $rules[] = [
            ['previewCurrency'], 'required', 'when' => function(): bool {
                return $this->previewFormat === self::FORMAT_CURRENCY;
            },
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
                'field' => $this,
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

        return $this->_normalizeNumber($value);
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
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        $view = Craft::$app->getView();
        $formatter = Craft::$app->getFormatter();

        try {
            $formatNumber = !$formatter->willBeMisrepresented($value);
        } catch (InvalidArgumentException $e) {
            $formatNumber = false;
        }

        if ($formatNumber) {
            if ($value !== null) {
                if ($this->previewFormat !== self::FORMAT_NONE) {
                    try {
                        $value = Craft::$app->getFormatter()->asDecimal($value, $this->decimals);
                    } catch (InvalidArgumentException $e) {
                    }
                } elseif ($this->decimals) {
                    // Just make sure we're using the right decimal symbol
                    $decimalSeparator = Craft::$app->getFormattingLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
                    try {
                        $value = number_format($value, $this->decimals, $decimalSeparator, '');
                    } catch (\Throwable $e) {
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

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Number/input', [
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
