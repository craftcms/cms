<?php
namespace Craft;

/**
 *
 */
abstract class BaseOptionsFieldType extends BaseFieldType
{
	protected $multi = false;

	/**
	 * Defines the settings.
	 *
	 * @access protected
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

		return craft()->templates->render('_components/fieldtypes/optionsfieldsettings', array(
			'label'   => $this->getOptionsSettingsLabel(),
			'class'   => $this->getClassHandle(),
			'options' => $options
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
	 * Returns the label for the Options setting.
	 *
	 * @abstract
	 * @access protected
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();

	/**
	 * Returns the field options, accounting for the old school way of saving them.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getOptions()
	{
		$oldOptions = $this->getSettings()->options;
		$newOptions = array();

		if (is_array($oldOptions))
		{
			foreach ($oldOptions as $key => $option)
			{
				// Old school?
				if (!is_array($option))
				{
					$newOptions[] = array('label' => $option, 'value' => $key, 'default' => '');
				}
				else
				{
					$newOptions[] = $option;
				}
			}
		}

		return $newOptions;
	}
}
