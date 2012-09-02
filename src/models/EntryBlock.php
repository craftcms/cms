<?php
namespace Blocks;

/**
 *
 */
class EntryBlock extends BaseBlockModel
{
	public function getTableName()
	{
		return 'entryblocks';
	}

	protected function getRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'Section'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('section_id', 'handle'), 'unique' => true)
		);
	}
}
