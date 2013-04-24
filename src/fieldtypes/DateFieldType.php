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
		return craft()->templates->render('_includes/forms/date', array(
			'id'    => preg_replace('/[\[\]]+/', '-', $name),
			'name'  => $name,
			'value' => $value
		));
	}

	/**
	 * Get the posted time and adjust it for timezones.
	 *
	 * @param mixed $value
	 * @return DateTime
	 */
	protected function prepPostData($value)
	{
		if ($value)
		{
			// Ugly?  Yes.  Yes it is.
			$timeString = $value->format(DateTime::MYSQL_DATETIME, DateTime::UTC);
			return DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $timeString, craft()->getTimeZone());
		}
	}

	/**
	 * Convert back to the server's timezone.
	 *
	 * @param mixed $value
	 * @return DateTime
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			return $value->setTimezone(new \DateTimeZone(craft()->getTimeZone()));
		}
	}
}
