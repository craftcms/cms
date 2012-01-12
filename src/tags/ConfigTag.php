<?php

/**
 *
 */
class ConfigTag extends Tag
{
	/**
	 * @return mixed
	 */
	public function configPath()
	{
		return Blocks::app()->path->blocksConfigPath;
	}

	/**
	 * @return mixed
	 */
	public function licenseKeys()
	{
		return Blocks::app()->site->licenseKeys;
	}
}
