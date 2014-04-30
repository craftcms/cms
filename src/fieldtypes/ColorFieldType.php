<?php
namespace Craft;

/**
 *
 */
class ColorFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Color');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 7);
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
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
}
