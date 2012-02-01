<?php
namespace Blocks;

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
		return Blocks::app()->path->configPath.'config.php';
	}

	/**
	 * @return mixed
	 */
	public function licenseKeys()
	{
		return Blocks::app()->site->licenseKeys;
	}
}
