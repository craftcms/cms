<?php
namespace Blocks;

/**
 * Block template variable class
 */
class BlockVariable extends BaseModelVariable
{
	/**
	 * Use the translated block name as its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Blocks::t($this->model->name);
	}

	/**
	 * Returns the model's settings errors.
	 *
	 * @return array
	 */
	public function settingErrors()
	{
		return $this->model->getSettingErrors();
	}

	/**
	 * Returns a block type variable based on this block model.
	 *
	 * @return BlockTypeVariable|null
	 */
	public function blockType()
	{
		$blockType = blx()->blockTypes->populateBlockType($this->model);
		if ($blockType)
		{
			return new BlockTypeVariable($blockType);
		}
	}
}