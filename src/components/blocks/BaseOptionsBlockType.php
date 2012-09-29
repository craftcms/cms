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
	 * Preprocesses settings values coming from setSettings() before they get saved to the settings model.
	 *
	 * @access protected
	 * @param array $values
	 * @return array
	 */
	protected function preprocessSettings($values)
	{
		// Expand the options setting into an array.
		if (!empty($values['options']) && is_string($values['options']))
		{
			$options = array();

			$lines = array_filter(preg_split('/[\r\n]+/', $values['options']));

			foreach($lines as $line)
			{
				$parts = preg_split('/=>/', $line, 2);
				$options[trim($parts[0])] = (isset($parts[1])) ? trim($parts[1]) : trim($parts[0]);
			}

			$values['options'] = $options;
		}

		return $values;
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return string|array
	 */
	public function defineContentAttribute()
	{
		if ($this->multi)
			return AttributeType::Mixed;
		else
			return AttributeType::String;
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
				$options .= $label."\n";
			else
				$options .= $value.' => '.$label."\n";
		}

		return blx()->templates->render('_components/blocks/optionsblocksettings', array(
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
