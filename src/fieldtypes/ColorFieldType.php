<?php
namespace Craft;

/**
 * Class ColorFieldType
 *
 * @package craft.app.fieldtypes
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

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param mixed $value
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
