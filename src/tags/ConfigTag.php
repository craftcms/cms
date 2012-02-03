<?php
namespace Blocks;

/**
 *
 */
class ConfigTag extends Tag
{

	/**
	 * Get a config item
	 */
	public function item($item)
	{
		return Blocks::app()->config->getItem($item);
	}

	/**
	 * @return mixed
	 */
	public function licenseKeys()
	{
		return Blocks::app()->sites->licenseKeys;
	}
}
