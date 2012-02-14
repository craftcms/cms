<?php
namespace Blocks;

class RadioButtonsBlock extends BaseBlock
{
	public $name = 'Radio Buttons';

	protected $settings = array(
		'options' => array()
	);

	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';

	/**
	 * Settings validation
	 * @return bool Whether the settings passed validation
	 */
	public function validateSettings()
	{
		if (empty($this->settings['options']))
			$this->errors['options'] = 'Radio Button Options cannot be blank.';

		return empty($this->errors);
	}

	/**
	 * Set the settings
	 */
	public function setSettings($settings)
	{
		parent::setSettings($settings);

		// Make sure that options is an array
		if (is_string($this->settings['options']))
			$this->settings['options'] = preg_split('/[\r\n]+/', $this->settings['options']);

		// Filter out any blank options
		$this->settings['options'] = array_filter($this->settings['options']);
	}

}
