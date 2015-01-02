<?php
namespace craft\app\fieldtypes
;
use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Color fieldtype
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     3.0
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
		return Craft::t('Color');
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 7);
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

		return craft()->templates->render('_includes/forms/color', array(
			'id'    => craft()->templates->formatInputId($name),
			'name'  => $name,
			'value' => $value,
		));
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
