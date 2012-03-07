<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlocktype extends BaseBlocktype
{
	protected $defaultSettings = array(
		'options' => array()
	);

	/**
	 * Set the settings
	 *
	 * @param $settings
	 */
	public function setSettings($settings)
	{
		// Make sure that options is an array
		if (isset($settings['options']) && is_string($settings['options']))
		{
			$settings['options'] = preg_split('/[\r\n]+/', $settings['options']);
		}

		return parent::setSettings($settings);
	}

}
