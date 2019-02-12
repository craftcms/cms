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
use craft\helpers\Db;
use craft\helpers\Localization;
use craft\i18n\Locale;

/**
 * Number represents a Number field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Number extends Field implements PreviewableFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Number');
    }

    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

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
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['min', 'max'], 'number'];
        $rules[] = [['decimals', 'size'], 'integer'];
        $rules[] = [
            ['max'],
            'compare',
            'compareAttribute' => 'min',
            'operator' => '>='
        ];

        if (!$this->decimals) {
            $rules[] = [['min', 'max'], 'integer'];
        }

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
        // Was this submitted with a locale ID?
        if (isset($value['locale'], $value['value'])) {
            $value = Localization::normalizeNumber($value['value'], $value['locale']);
        }

        return $value === '' ? null : $value;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if ($this->isFresh($element) && $this->defaultValue !== null) {
            $value = $this->defaultValue;
        }

        // If decimals is 0 (or null, empty for whatever reason), don't run this
        if ($value !== null && $this->decimals) {
            $decimalSeparator = Craft::$app->getLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
            try {
                $value = number_format($value, $this->decimals, $decimalSeparator, '');
            } catch (\Throwable $e) {
                // NaN
            }
        }

        return '<input type="hidden" name="' . $this->handle . '[locale]" value="' . Craft::$app->language . '">' .
            Craft::$app->getView()->renderTemplate('_includes/forms/text', [
                'name' => $this->handle . '[value]',
                'value' => $value,
                'size' => $this->size
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
}
