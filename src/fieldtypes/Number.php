<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\helpers\LocalizationHelper;
use craft\app\helpers\ModelHelper;

/**
 * Number fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Number extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Number');
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return Craft::$app->templates->render('_components/fieldtypes/Number/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		$attribute = ModelHelper::getNumberAttributeConfig($this->settings->min, $this->settings->max, $this->settings->decimals);
		$attribute['default'] = 0;

		return $attribute;
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		if ($this->isFresh() && ($value < $this->settings->min || $value > $this->settings->max))
		{
			$value = $this->settings->min;
		}

		return Craft::$app->templates->render('_includes/forms/text', array(
			'name'  => $name,
			'value' => Craft::$app->numberFormatter->formatDecimal($value, false),
			'size'  => 5
		));
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValueFromPost()
	 *
	 * @param mixed $data
	 *
	 * @return mixed
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return [
			'min'      => [AttributeType::Number, 'default' => 0],
			'max'      => [AttributeType::Number, 'compare' => '>= min'],
			'decimals' => [AttributeType::Number, 'default' => 0],
		];
	}
}
