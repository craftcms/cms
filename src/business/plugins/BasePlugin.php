<?php
namespace Blocks;

/**
 * Plugin base class
 */
abstract class BasePlugin extends BaseComponent
{
	public $name;
	public $version;
	public $developer;
	public $developerUrl;

	protected $componentType = 'Plugin';

	/**
	 * Returns whether the plugin is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (isset($this->record))
			return true;

		$class = $this->getClassHandle();
		$installed = blx()->plugins->isPluginInstalled($class);
		return $installed;
	}
}
