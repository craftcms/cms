<?php
namespace Craft;

/**
 * Override the default \CStatePersister so we can set a custom path at runtime for our state file.
 *
 * @package craft.app.etc.state
 */
class StatePersister extends \CStatePersister
{
	/**
	 * Init
	 */
	public function init()
	{
		$this->stateFile = craft()->path->getStatePath().'state.bin';
		parent::init();
	}

	/**
	 * Saves application state in persistent storage.
	 *
	 * @param mixed $state state data (must be serializable).
	 */
	public function save($state)
	{
		IOHelper::writeToFile($this->stateFile, serialize($state));
	}
}
