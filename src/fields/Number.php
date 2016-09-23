<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Element;
use craft\app\base\Field;
use craft\app\base\PreviewableFieldInterface;
use craft\app\helpers\Db;
use craft\app\helpers\Localization;
use craft\app\i18n\Locale;

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
    public static function displayName()
    {
        return Craft::t('app', 'Number');
    }

    // Properties
    // =========================================================================

    /**
     * @var integer|float The minimum allowed number
     */
    public $min = 0;

    /**
     * @var integer|float The maximum allowed number
     */
    public $max;

    /**
     * @var integer The number of digits allowed after the decimal point
     */
    public $decimals = 0;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['min', 'max'], 'number'];
        $rules[] = [['decimals'], 'integer'];
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
    public function getContentColumnType()
    {
        return Db::getNumericalColumnType($this->min, $this->max, $this->decimals);
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        /** @var Element $element */
        // Is this a post request?
        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest() && $request->getIsPost()) {
            // Normalize the number and make it look like this is what was posted
            if ($value === '') {
                $value = 0;
            } else {
                $value = Localization::normalizeNumber($value);
            }
            $element->setRawPostValueForField($this->handle, $value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
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
            'size' => 5
        ]);
    }
}
