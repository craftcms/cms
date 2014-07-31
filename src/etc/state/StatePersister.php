<?php
namespace Craft;

/**
 * Override the default \CStatePersister so we can set a custom path at runtime for our state file.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.state
 * @since     1.0
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
