<?php
namespace Blocks;

/**
 * Section template variable
 */
class SectionVariable extends ModelVariable
{
	/**
	 * Returns a section's blocks.
	 *
	 * @return array
	 */
	public function blocks()
	{
		$blocks = blx()->blocks->populateBlocks($this->model->blocks);
		return VariableHelper::populateVariables($blocks, 'BlockVariable');
	}

	/**
	 * Retruns the total number of blocks.
	 *
	 * @return int
	 */
	public function totalBlocks()
	{
		return $this->model->totalBlocks;
	}
}
