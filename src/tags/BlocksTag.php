<?php
namespace Blocks;

/**
 *
 */
class BlocksTag extends Tag
{
	/**
	 * @return string
	 */
	public function edition()
	{
		return Blocks::getEdition();
	}

	/**
	 * @return string
	 */
	public function version()
	{
		return Blocks::getVersion();
	}

	/**
	 * @return string
	 */
	public function build()
	{
		return Blocks::getBuild();
	}

	/**
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
	 * @return ConfigTag
	 */
	public function config()
	{
		return new ConfigTag;
	}
}
