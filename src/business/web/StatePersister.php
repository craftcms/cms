<?php
namespace Blocks;

/**
 * Override the default CStatePersister so we can set a custom path at runtime for our state file.
 */
class StatePersister extends \CStatePersister
{
	/**
	 *
	 */
	public function init()
	{
		$this->stateFile = b()->path->statePath.'state.bin';
		parent::init();
	}
}
