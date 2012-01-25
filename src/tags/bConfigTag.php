<?php

/**
 *
 */
class bConfigTag extends bTag
{
	/**
	 * @return mixed
	 */
	public function configPath()
	{
		return BLOCKS_CONFIG_PATH.'config.php';
	}

	/**
	 * @return mixed
	 */
	public function licenseKeys()
	{
		return Blocks::app()->site->licenseKeys;
	}
}
