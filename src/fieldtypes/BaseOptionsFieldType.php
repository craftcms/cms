<?php
namespace Craft;

/**
 * Class BaseOptionsFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
abstract class BaseOptionsFieldType extends BaseFieldType implements IPreviewableFieldType
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	protected $multi = false;

	/**
	 * @var
	 */
	private $_options;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		if ($this->multi)
		{
			$options = $this->getSettings()->options;

			// See how much data we could possibly be saving if everything was selected.
			$length = 0;

			foreach ($options as $option)
			{
				if (!empty($option['value']))
				{
					// +3 because it will be json encoded. Includes the surrounding quotes and comma.
					$length += strlen($option['value']) + 3;
				}
			}

			if ($length)
			{
				// Add +2 for the outer brackets and -1 for the last comma.
				$length += 1;

				$columnType = DbHelper::getTextualColumnTypeByContentLength($length);
			}
			else
			{
				$columnType = ColumnType::Varchar;
			}

			return array(AttributeType::Mixed, 'column' => $columnType, 'default' => $this->getDefaultValue());
		}
		else
		{
			return array(AttributeType::String, 'column' => ColumnType::Varchar, 'maxLength' => 255, 'default' => $this->getDefaultValue());
		}
	}

	/**
	 * @inheritDoc BaseElementFieldType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$options = $this->getOptions();

		if (!$options)
		{
			// Give it a default row
			$options = array(array('label' => '', 'value' => ''));
		}

		return craft()->templates->renderMacro('_includes/forms', 'editableTableField', array(
			array(
				'label'        => $this->getOptionsSettingsLabel(),
				'instructions' => Craft::t('Define the available options.'),
				'id'           => 'options',
				'name'         => 'options',
				'addRowLabel'  => Craft::t('Add an option'),
				'cols'         => array(
					'label' => array(
						'heading'      => Craft::t('Option Label'),
						'type'         => 'singleline',
						'autopopulate' => 'value'
					),
					'value' => array(
						'heading'      => Craft::t('Value'),
						'type'         => 'singleline',
						'class'        => 'code'
					),
					'default' => array(
						'heading'      => Craft::t('Default?'),
						'type'         => 'checkbox',
						'class'        => 'thin'
					),
				),
				'rows' => $options
			)
		));
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
		if (!empty($settings['options']))
		{
			// Drop the string row keys
			$settings['options'] = array_values($settings['options']);
		}

		return $settings;
	}

	/**
	 * @inheritDoc IFieldType::validate()
	 *
	 * @param mixed $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		// If there is no value, we're good
		if (!$value)
		{
			return true;
		}

		$valid = true;

		// Get all of the acceptable values
		$acceptableValues = array();

		foreach ($this->getOptions() as $option)
		{
			$acceptableValues[] = $option['value'];
		}

		if ($this->multi)
		{
			// Make sure $value is actually an array
			if (!is_array($value))
			{
				$valid = false;
			}
			else
			{
				// Make sure that each of the values are on the list
				foreach ($value as $val)
				{
					if ($val !== '' && !in_array($val, $acceptableValues))
					{
						$valid = false;
						break;
					}
				}
			}
		}
		else
		{
			// Make sure that the value is on the list
			if (!in_array($value, $acceptableValues))
			{
				$valid = false;
			}
		}

		if (!$valid)
		{
			return Craft::t('{attribute} is invalid.', array(
				'attribute' => Craft::t($this->model->name)
			));
		}

		// All good
		return true;
	}

	/**
	 * @inheritDoc IPreviewableFieldType::getTableAttributeHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getTableAttributeHtml($value)
	{
		if ($this->multi)
		{
			/** @var MultiOptionsFieldData $value */
			$labels = array();

			foreach ($value as $option)
			{
				$labels[] = $option->label;
			}

			return implode(', ', $labels);
		}
		else
		{
			/** @var SingleOptionFieldData $value */
			return $value->label;
		}
	}

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		$selectedValues = ArrayHelper::stringToArray($value);

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
				$value = array();
			}

			$value = new MultiOptionsFieldData($value);
		}
		else
		{
			// Convert the value to a SingleOptionFieldData object
			$label = $this->getOptionLabel($value);
			$value = new SingleOptionFieldData($label, $value, true);
		}

		$options = array();

		foreach ($this->getOptions() as $option)
		{
			$selected = in_array($option['value'], $selectedValues, true);
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
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'options' => array(AttributeType::Mixed, 'default' => array())
		);
	}

	/**
	 * Returns the field options, accounting for the old school way of saving them.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		if (!isset($this->_options))
		{
			$this->_options = array();

			$options = $this->getSettings()->options;

			if (is_array($options))
			{
				foreach ($options as $key => $option)
				{
					// Old school?
					if (!is_array($option))
					{
						$this->_options[] = array('label' => $option, 'value' => $key, 'default' => '');
					}
					else
					{
						$this->_options[] = $option;
					}
				}
			}
		}

		return $this->_options;
	}

	/**
	 * Returns the field options, with labels run through Craft::t().
	 *
	 * @return array
	 */
	protected function getTranslatedOptions()
	{
		$options = $this->getOptions();

		foreach ($options as &$option)
		{
			$option['label'] = Craft::t($option['label']);
		}

		return $options;
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
		foreach ($this->getOptions() as $option)
		{
			if ((string)$option['value'] === (string)$value)
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
			$defaultValues = array();
		}

		foreach ($this->getOptions() as $option)
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
