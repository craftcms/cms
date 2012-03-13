<?php
namespace Blocks;

/**
 *
 */
class AppTag
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
}
