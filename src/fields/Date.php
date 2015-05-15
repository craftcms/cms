<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\DbHelper;
use yii\db\Schema;

/**
 * Date represents a Date/Time field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Date extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Date/Time');
	}

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (isset($config['dateTime']))
		{
			switch ($config['dateTime'])
			{
				case 'showBoth':
				{
					unset($config['dateTime']);
					$config['showTime'] = true;
					$config['showDate'] = true;

					break;
				}
				case 'showDate':
				{
					unset($config['dateTime']);
					$config['showDate'] = true;
					$config['showTime'] = false;

					break;
				}
				case 'showTime':
				{
					unset($config['dateTime']);
					$config['showTime'] = true;
					$config['showDate'] = false;

					break;
				}
			}
		}

		return parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var boolean Whether a datepicker should be shown as part of the input
	 */
	public $showDate = true;

	/**
	 * @var boolean Whether a timepicker should be shown as part of the input
	 */
	public $showTime = false;

	/**
	 * @var integer The number of minutes that the timepicker options should increment by
	 */
	public $minuteIncrement = 30;

	// Public Methods
	// =========================================================================

	public function init()
	{
		parent::init();

		// In case nothing is selected, default to the date.
		if (!$this->showDate && !$this->showTime)
		{
			$this->showDate = true;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = [['showDate', 'showTime'], 'boolean'];
		$rules[] = [['minuteIncrement'], 'integer', 'min' => 1, 'max' => 60];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		return Schema::TYPE_DATETIME;
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		// If they are both selected or nothing is selected, the select showBoth.
		if (($this->showDate && $this->showTime))
		{
			$dateTimeValue = 'showBoth';
		}
		else if ($this->showDate)
		{
			$dateTimeValue = 'showDate';
		}
		else if ($this->showTime)
		{
			$dateTimeValue = 'showTime';
		}

		$options = [15, 30, 60];
		$options = array_combine($options, $options);

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Date/settings', [
			'options' => [
				[
					'label' => Craft::t('app', 'Show date'),
					'value' => 'showDate',
				],
				[
					'label' => Craft::t('app', 'Show time'),
					'value' => 'showTime',
				],
				[
					'label' => Craft::t('app', 'Show date and time'),
					'value' => 'showBoth',
				]
			],
			'value' => $dateTimeValue,
			'incrementOptions' => $options,
			'field' => $this,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		$variables = [
			'id'              => Craft::$app->getView()->formatInputId($this->handle),
			'name'            => $this->handle,
			'value'           => $value,
			'minuteIncrement' => $this->minuteIncrement
		];

		$input = '';

		if ($this->showDate)
		{
			$input .= Craft::$app->getView()->renderTemplate('_includes/forms/date', $variables);
		}

		if ($this->showTime)
		{
			$input .= ' '.Craft::$app->getView()->renderTemplate('_includes/forms/time', $variables);
		}

		return $input;
	}

	/**
	 * @inheritdoc
	 */
	public function prepareValue($value, $element)
	{
		if ($value)
		{
			$value = DateTimeHelper::toDateTime($value);
		}

		return $value;
	}

	/**
	 * @inheritdoc
	 */
	public function modifyElementsQuery(ElementQueryInterface $query, $value)
	{
		if ($value !== null)
		{
			$handle = $this->handle;
			/** @var ElementQuery $query */
			$query->subQuery->andWhere(DbHelper::parseDateParam('content.'.Craft::$app->getContent()->fieldColumnPrefix.$handle, $value, $query->subQuery->params));
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function prepareValueBeforeSave($value, $element)
	{
		if ($value)
		{
			$value = DateTimeHelper::toDateTime($value);
		}

		return $value;
	}
}
