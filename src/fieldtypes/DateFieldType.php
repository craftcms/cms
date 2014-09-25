<?php
namespace Craft;

/**
 * Class DateFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class DateFieldType extends BaseFieldType
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
		return Craft::t('Date/Time');
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::DateTime;
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// If they are both selected or nothing is selected, the select showBoth.
		if (($this->getSettings()->showDate && $this->getSettings()->showTime))
		{
			$dateTimeValue = 'showBoth';
		}
		else if ($this->getSettings()->showDate)
		{
			$dateTimeValue = 'showDate';
		}
		else if ($this->getSettings()->showTime)
		{
			$dateTimeValue = 'showTime';
		}

		$options = array(15, 30, 60);
		$options = array_combine($options, $options);

		return craft()->templates->render('_components/fieldtypes/Date/settings', array(
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
			'value' => $dateTimeValue,
			'incrementOptions' => $options,
			'settings' => $this->getSettings(),
		));
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
		$variables = array(
			'id'              => craft()->templates->formatInputId($name),
			'name'            => $name,
			'value'           => $value,
			'minuteIncrement' => $this->getSettings()->minuteIncrement
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
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
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
	 * @inheritDoc IFieldType::modifyElementsQuery()
	 *
	 * @param DbCommand $query
	 * @param mixed     $value
	 *
	 * @return null|false
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
	 * @inheritDoc ISavableComponentType::prepSettings()
	 *
	 * @param array $settings
	 *
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'showDate'        => array(AttributeType::Bool, 'default' => true),
			'showTime'        => AttributeType::Bool,
			'minuteIncrement' => array(AttributeType::Number, 'default' => 30, 'min' => 1, 'max' => 60),
		);
	}
}
