<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use craft\app\Craft;
use craft\app\dates\DateTime;
use craft\app\db\DbCommand;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;

/**
 * Date fieldtype
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Date extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Date/Time');
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::DateTime;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
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

		$options = [15, 30, 60];
		$options = array_combine($options, $options);

		return Craft::$app->templates->render('_components/fieldtypes/Date/settings', [
			'options' => [
				[
					'label' => Craft::t('Show date'),
					'value' => 'showDate',
				],
				[
					'label' => Craft::t('Show time'),
					'value' => 'showTime',
				],
				[
					'label' => Craft::t('Show date and time'),
					'value' => 'showBoth',
				]
			],
			'value' => $dateTimeValue,
			'incrementOptions' => $options,
			'settings' => $this->getSettings(),
		]);
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$variables = [
			'id'              => Craft::$app->templates->formatInputId($name),
			'name'            => $name,
			'value'           => $value,
			'minuteIncrement' => $this->getSettings()->minuteIncrement
		];

		$input = '';

		// In case nothing is selected, default to the date.
		if (!$this->getSettings()->showDate && !$this->getSettings()->showTime)
		{
			$this->getSettings()->showDate = true;
		}

		if ($this->getSettings()->showDate)
		{
			$input .= Craft::$app->templates->render('_includes/forms/date', $variables);
		}

		if ($this->getSettings()->showTime)
		{
			$input .= ' '.Craft::$app->templates->render('_includes/forms/time', $variables);
		}

		return $input;
	}

	/**
	 * @inheritDoc FieldTypeInterface::prepValue()
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
			$timezone = Craft::$app->getTimeZone();
			$value->setTimezone(new \DateTimeZone($timezone));

			return $value;
		}
	}

	/**
	 * @inheritDoc FieldTypeInterface::modifyElementsQuery()
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
			$query->andWhere(DbHelper::parseDateParam('content.'.Craft::$app->content->fieldColumnPrefix.$handle, $value, $query->params));
		}
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::prepSettings()
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
		return [
			'showDate'        => [AttributeType::Bool, 'default' => true],
			'showTime'        => AttributeType::Bool,
			'minuteIncrement' => [AttributeType::Number, 'default' => 30, 'min' => 1, 'max' => 60],
		];
	}
}
