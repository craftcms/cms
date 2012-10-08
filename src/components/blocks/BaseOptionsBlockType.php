<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlockType extends BaseBlockType
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
	 * Preprocesses the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function preprocessSettings($settings)
	{
		// Expand the options setting into an array.
		if (!empty($settings['options']) && is_string($settings['options']))
		{
			$options = array();

			$lines = array_filter(preg_split('/[\r\n]+/', $settings['options']));

			foreach($lines as $line)
			{
				$parts = preg_split('/=>/', $line, 2);
				$options[trim($parts[0])] = (isset($parts[1])) ? trim($parts[1]) : trim($parts[0]);
			}

			$settings['options'] = $options;
		}

		return $settings;
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
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Prepare the options array for the textarea
		$options = '';

		foreach ($this->getSettings()->options as $value => $label)
		{
			if ((string)$value === (string)$label)
			{
				$options .= $label."\n";
			}
			else
			{
				$options .= $value.' => '.$label."\n";
			}
		}

		return blx()->templates->render('_components/blocktypes/optionsblocksettings', array(
			'label'   => $this->getOptionsSettingsLabel(),
			'options' => $options
		));
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @abstract
	 * @access protected
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();
}
