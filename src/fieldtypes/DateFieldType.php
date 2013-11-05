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
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'showTime' => AttributeType::Bool,
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->renderMacro('_includes/forms.html', 'checkboxField', array(
			array(
				'label' => Craft::t('Show time?'),
				'id' => 'showTime',
				'name' => 'showTime',
				'checked' => $this->getSettings()->showTime,
			)
		));
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
		$variables = array(
			'id'       => craft()->templates->formatInputId($name),
			'name'     => $name,
			'value'    => $value
		);

		$input = craft()->templates->render('_includes/forms/date', $variables);

		if ($this->getSettings()->showTime)
		{
			$input .= ' '.craft()->templates->render('_includes/forms/time', $variables);
		}

		return $input;
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if ($value)
		{
			// Ugly? Yes. Yes it is.
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

	/**
	 * Modifies an element query that's filtering by this field.
	 *
	 * @param DbCommand $query
	 * @param mixed     $value
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value)
	{
		$handle = $this->model->handle;
		$query->andWhere(DbHelper::parseDateParam('content.'.craft()->content->fieldColumnPrefix.$handle, $value, $query->params));
	}
}
