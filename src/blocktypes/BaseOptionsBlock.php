<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlock extends BaseBlock
{
	/**
	 * @return array
	 */
	protected function getDefaultSettings()
	{
		return array(
			'options' => array()
		);
	}

	/**
	 * Set the settings
	 *
	 * @param $settings
	 * @return mixed
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
