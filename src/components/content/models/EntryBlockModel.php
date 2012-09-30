<?php
namespace Blocks;

/**
 * Entry block model class.
 */
class EntryBlockModel extends BaseBlockModel
{
	/**
	 * Saves the entry block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->entryBlocks->saveBlock($this);
	}
}
