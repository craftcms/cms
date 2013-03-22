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
		return array(AttributeType::DateTime, 'column' => ColumnType::DateTime);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			if (is_string($value))
			{
				$value = DateTime::createFromString($value);
			}
		}
		else
		{
			$value = null;
		}

		return $value;
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		return $this->prepValue($value);
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
