<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
 * @since  3.0
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

        // Normalize $max
        if ($this->max !== null && empty($this->max)) {
            $this->max = null;
        }

        // Normalize $min
        if ($this->min !== null && empty($this->min)) {
            $this->min = null;
        }

        // Normalize $size
        if ($this->size !== null && empty($this->size)) {
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
        // Is this a post request?
        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsPost()) {
            // Normalize the number and make it look like this is what was posted
            if ($value === '') {
                $value = 0;
            } else {
                $value = Localization::normalizeNumber($value);
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if ($this->isFresh($element) && ($value < $this->min || $value > $this->max)) {
            $value = $this->min;
        }

        $decimals = $this->decimals;
        $decimalSeparator = Craft::$app->getLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
        $value = number_format($value, $decimals, $decimalSeparator, '');

        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'name' => $this->handle,
            'value' => $value,
            'size' => $this->size
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = ['number', 'min' => $this->min ?: null, 'max' => $this->max ?: null];

        return $rules;
    }
}
