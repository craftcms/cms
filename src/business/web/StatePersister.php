<?php
namespace Blocks;

/**
 *
 */
class StatePersister extends \CStatePersister
{
	public function init()
	{
		$this->stateFile = b()->path->statePath.'state.bin';
		parent::init();
	}
}
