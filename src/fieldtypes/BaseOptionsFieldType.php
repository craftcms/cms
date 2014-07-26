<?php
namespace Craft;

/**
 * Class BaseOptionsFieldType
 *
 * @package craft.app.fieldtypes
 */
abstract class BaseOptionsFieldType extends BaseFieldType
{
	protected $multi = false;
	private $_options;

	/**
	 * Defines the settings.
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
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		if ($this->multi)
		{
			return AttributeType::Mixed;
		}
		else
		{
			return AttributeType::String;
		}
	}

	/**
	 * Returns the field's settings HTML.
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

		$class = $this->getClassHandle();

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
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
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
	 * Preps the field value for use.
	 *
	 * @param mixed $value
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
			$selected = in_array($option['value'], $selectedValues);
			$options[] = new OptionData($option['label'], $option['value'], $selected);
		}

		$value->setOptions($options);

		return $value;
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();

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
	 * @return string
	 */
	protected function getOptionLabel($value)
	{
		foreach ($this->getOptions() as $option)
		{
			if ($option['value'] == $value)
			{
				return $option['label'];
			}
		}

		return $value;
	}
}
