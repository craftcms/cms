<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\helpers\DbHelper;
use craft\app\helpers\LocalizationHelper;
use craft\app\i18n\Locale;

/**
 * Number represents a Number field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Number extends Field
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
		$rules[] = [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/fieldtypes/Number/settings', [
			'field' => $this
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return DbHelper::getNumericalColumnType($this->min, $this->max, $this->decimals);
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($name, $value)
	{
		if ($this->isFresh() && ($value < $this->min || $value > $this->max))
		{
			$value = $this->min;
		}

		$decimals = $this->decimals;
		$decimalSeparator = Craft::$app->getLocale()->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
		$value = number_format($value, $decimals, $decimalSeparator, '');

		return Craft::$app->templates->render('_includes/forms/text', [
			'name'  => $name,
			'value' => $value,
			'size'  => 5
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepValueFromPost($data)
	{
		if ($data === '')
		{
			return 0;
		}
		else
		{
			return LocalizationHelper::normalizeNumber($data);
		}
	}
}
