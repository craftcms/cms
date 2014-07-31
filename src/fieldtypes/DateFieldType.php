<?php
namespace Craft;

/**
 * Class DateFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
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
		return Craft::t('Date/Time');
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
					'label' => Craft::t('Show date'),
					'value' => 'showDate',
				),
				array(
					'label' => Craft::t('Show time'),
					'value' => 'showTime',
				),
				array(
					'label' => Craft::t('Show date and time'),
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
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return DateTime
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			// Set it to the system timezone
			$timezone = craft()->getTimeZone();
			$value->setTimezone(new \DateTimeZone($timezone));

			return $value;
		}
	}

	/**
	 * Modifies an element query that's filtering by this field.
	 *
	 * @param DbCommand $query
	 * @param mixed     $value
	 * @return void|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value)
	{
		if ($value !== null)
		{
			$handle = $this->model->handle;
			$query->andWhere(DbHelper::parseDateParam('content.'.craft()->content->fieldColumnPrefix.$handle, $value, $query->params));
		}
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
