<?php

/**
 *
 */
class ConfigTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function configPath()
	{
		return Blocks::app()->path->blocksConfigPath;
	}

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function licenseKeys()
	{
		return Blocks::app()->site->licenseKeys;
	}
}
