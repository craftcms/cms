<?php
namespace Craft;

/**
 *
 */
class DateFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Date');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::DateTime;
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
		return craft()->templates->render('_components/fieldtypes/Date/input', array(
			'id'    => preg_replace('/[\[\]]+/', '-', $name),
			'name'  => $name,
			'value' => $value
		));
	}
}
