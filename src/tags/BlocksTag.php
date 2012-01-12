<?php

/**
 *
 */
class BlocksTag extends Tag
{
	/**
	 * @access public
	 *
	 * @return string
	 */
	public function edition()
	{
		return Blocks::getEdition();
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function version()
	{
		return Blocks::getVersion();
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function build()
	{
		return Blocks::getBuild();
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function fullVersion()
	{
		$edition = Blocks::getEdition();
		$name = 'Blocks'.($edition != 'Standard' ? ' '.$edition : '');
		$version = Blocks::getVersion();
		$build = Blocks::getBuild();

		return "{$name} {$version}.{$build}";
	}

	/**
	 * @access public
	 *
	 * @return ConfigTag
	 */
	public function config()
	{
		return new ConfigTag;
	}
}
