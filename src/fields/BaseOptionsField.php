<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\fields\data\MultiOptionsFieldData;
use craft\app\fields\data\OptionData;
use craft\app\fields\data\SingleOptionFieldData;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DbHelper;
use yii\db\Schema;

/**
 * BaseOptionsField is the base class for classes representing an options field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseOptionsField extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (!empty($config['options']))
		{
			// Drop the string row keys
			$config['options'] = array_values($config['options']);
		}

		return parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var array The available options
	 */
	public $options;

	/**
	 * @var boolean Whether the field should support multiple selections
	 */
	protected $multi = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// Normalize the options
		$options = [];

		if (is_array($this->options))
		{
			foreach ($this->options as $key => $option)
			{
				// Old school?
				if (!is_array($option))
				{
					$options[] = ['label' => $option, 'value' => $key, 'default' => ''];
				}
				else
				{
					$options[] = $option;
				}
			}
		}

		$this->options = $options;
	}

	/**
	 * @inheritdoc
	 */
	public function settingsAttributes()
	{
		$attributes = parent::settingsAttributes();
		$attributes[] = 'options';
		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function getContentColumnType()
	{
		if ($this->multi)
		{
			// See how much data we could possibly be saving if everything was selected.
			$length = 0;

			foreach ($this->options as $option)
			{
				if (!empty($option['value']))
				{
					// +3 because it will be json encoded. Includes the surrounding quotes and comma.
					$length += strlen($option['value']) + 3;
				}
			}

			// Add +2 for the outer brackets and -1 for the last comma.
			$length += 1;

			return DbHelper::getTextualColumnTypeByContentLength($length);
		}
		else
		{
			return Schema::TYPE_STRING;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		if (!$this->options)
		{
			// Give it a default row
			$this->options = [['label' => '', 'value' => '']];
		}

		return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField', [
			[
				'label'        => $this->getOptionsSettingsLabel(),
				'instructions' => Craft::t('app', 'Define the available options.'),
				'id'           => 'options',
				'name'         => 'options',
				'addRowLabel'  => Craft::t('app', 'Add an option'),
				'cols'         => [
					'label' => [
						'heading'      => Craft::t('app', 'Option Label'),
						'type'         => 'singleline',
						'autopopulate' => 'value'
					],
					'value' => [
						'heading'      => Craft::t('app', 'Value'),
						'type'         => 'singleline',
						'class'        => 'code'
					],
					'default' => [
						'heading'      => Craft::t('app', 'Default?'),
						'type'         => 'checkbox',
						'class'        => 'thin'
					],
				],
				'rows' => $this->options
			]
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function prepareValue($value, $element)
	{
		$selectedValues = ArrayHelper::toArray($value);

		if ($this->multi)
		{
			if (is_array($value))
			{
				// Convert all the values to OptionData objects
				foreach ($value as &$val)
				{
					$label = $this->getOptionLabel($val);
					$val = new OptionData($label, $val, true);
				}
			}
			else
			{
				$value = [];
			}

			$value = new MultiOptionsFieldData($value);
		}
		else
		{
			// Convert the value to a SingleOptionFieldData object
			$label = $this->getOptionLabel($value);
			$value = new SingleOptionFieldData($label, $value, true);
		}

		$options = [];

		foreach ($this->options as $option)
		{
			$selected = in_array($option['value'], $selectedValues);
			$options[] = new OptionData($option['label'], $option['value'], $selected);
		}

		$value->setOptions($options);

		return $value;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the label for the Options setting.
	 *
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();

	/**
	 * Returns the field options, with labels run through Craft::t().
	 *
	 * @return array
	 */
	protected function getTranslatedOptions()
	{
		$translatedOptions = [];

		foreach ($this->options as $option)
		{
			$translatedOptions[] = ['label' => Craft::t('app', $option['label']), 'value' => $option['value']];
		}

		return $translatedOptions;
	}

	/**
	 * Returns an option's label by its value.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function getOptionLabel($value)
	{
		foreach ($this->options as $option)
		{
			if ($option['value'] == $value)
			{
				return $option['label'];
			}
		}

		return $value;
	}

	/**
	 * Returns the default field value.
	 *
	 * @return array|string|null
	 */
	protected function getDefaultValue()
	{
		if ($this->multi)
		{
			$defaultValues = [];
		}

		foreach ($this->options as $option)
		{
			if (!empty($option['default']))
			{
				if ($this->multi)
				{
					$defaultValues[] = $option['value'];
				}
				else
				{
					return $option['value'];
				}
			}
		}

		if ($this->multi)
		{
			return $defaultValues;
		}
	}
}
