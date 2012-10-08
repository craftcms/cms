<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseBlockEntityRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrycontent';
	}

	/**
	 * Returns the list of blocks associated with this content.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->entryBlocks->getAllBlocks();
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}
}
