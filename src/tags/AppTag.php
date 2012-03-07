<?php
namespace Blocks;

/**
 *
 */
class AppTag extends Tag
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
		$version = Blocks::getVersion();
		$build = Blocks::getBuild();

		return $version.'.'.$build;
	}

	/**
	 * @return ConfigTag
	 */
	public function config()
	{
		return new ConfigTag;
	}
}
