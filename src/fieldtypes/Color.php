<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes
;
use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Color fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Color extends BaseFieldType
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
		return Craft::t('app', 'Color');
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return [AttributeType::String, 'column' => ColumnType::Char, 'length' => 7];
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
		// Default to black, so the JS-based color picker is consistent with Chrome
		if (!$value)
		{
			$value = '#000000';
		}

		return Craft::$app->templates->render('_includes/forms/color', [
			'id'    => Craft::$app->templates->formatInputId($name),
			'name'  => $name,
			'value' => $value,
		]);
	}

	/**
	 * @inheritDoc BaseFieldType::getStaticHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		if ($value)
		{
			return '<div class="color" style="cursor: default;"><div class="colorpreview" style="background-color: '.$value.';"></div></div>' .
				'<div class="colorhex">'.$value.'</div>';
		}
	}
}
