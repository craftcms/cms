<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlock extends BaseBlock
{
	protected $settings = array(
		'options' => array()
	);

	/**
	 * Set the settings
	 *
	 * @param $settings
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

	/**
	 * Settings validation
	 * @return bool Whether the settings passed validation
	 */
	public function validateSettings()
	{
		if (empty($this->settings['options']))
			$this->errors['options'] = 'Options cannot be blank.';

		return empty($this->errors);
	}

}
