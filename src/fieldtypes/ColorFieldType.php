<?php
namespace Craft;

/**
 * Class ColorFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class ColorFieldType extends BaseFieldType implements IPreviewableFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Color');
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 7);
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
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
	 * @inheritDoc IFieldType::getStaticHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		if ($value)
		{
			return HtmlHelper::encodeParams('<div class="color" style="cursor: default;"><div class="colorpreview" style="background-color: {bgColor};"></div></div>'.
				'<div class="colorhex code">{bgColor}</div>', array('bgColor' => $value));
		}
	}

	/**
	 * @inheritDoc IPreviewableFieldType::getTableAttributeHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getTableAttributeHtml($value)
	{
		if ($value && $value != '#000000')
		{
			return '<div class="color small static"><div class="colorpreview" style="background-color: '.$value.';"></div></div>'.
				'<div class="colorhex code">'.$value.'</div>';
		}
		else
		{
			return '';
		}
	}
}
