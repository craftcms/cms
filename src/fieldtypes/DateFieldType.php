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
			'showDate' => array(AttributeType::Bool, 'default' => true),
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
		// If they are both selected or nothing is selected, the select showBoth.
		if (($this->getSettings()->showDate && $this->getSettings()->showTime))
		{
			$value = 'showBoth';
		}
		else if ($this->getSettings()->showDate)
		{
			$value = 'showDate';
		}
		else if ($this->getSettings()->showTime)
		{
			$value = 'showTime';
		}

		return craft()->templates->renderMacro('_includes/forms.html', 'radioGroupField', array(array(
			'id' => 'dateTime',
			'name' => 'dateTime',
			'options' => array(
				array(
					'label' => Craft::t('Show date?'),
					'value' => 'showDate',
				),
				array(
					'label' => Craft::t('Show time?'),
					'value' => 'showTime',
				),
				array(
					'label' => Craft::t('Show both?'),
					'value' => 'showBoth',
				)
			),
			'value' => $value,
		)));
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

		$input = '';

		// In case nothing is selected, default to the date.
		if (!$this->getSettings()->showDate && !$this->getSettings()->showTime)
		{
			$this->getSettings()->showDate = true;
		}

		if ($this->getSettings()->showDate)
		{
			$input .= craft()->templates->render('_includes/forms/date', $variables);
		}

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

	/**
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if (isset($settings['dateTime']))
		{
			switch ($settings['dateTime'])
			{
				case 'showBoth':
				{
					unset($settings['dateTime']);
					$settings['showTime'] = true;
					$settings['showDate'] = true;

					break;
				}
				case 'showDate':
				{
					unset($settings['dateTime']);
					$settings['showDate'] = true;
					$settings['showTime'] = false;

					break;
				}
				case 'showTime':
				{
					unset($settings['dateTime']);
					$settings['showTime'] = true;
					$settings['showDate'] = false;

					break;
				}
			}
		}

		return $settings;
	}
}
