<?php
namespace Blocks;

/**
 * Plugin functions
 */
class PluginsVariable
{
	/**
	 * Returns a plugin
	 */
	public function get($class)
	{
		return b()->plugins->getPlugin($class);
	}

	/**
	 * Returns all plugins
	 */
	public function all()
	{
		return b()->plugins->getAll(true, true);
	}
}
