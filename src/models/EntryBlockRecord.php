<?php
namespace Blocks;

/**
 *
 */
class EntryBlockRecord extends BaseBlockRecord
{
	public function getTableName()
	{
		return 'entryblocks';
	}

	protected function getRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('sectionId', 'handle'), 'unique' => true)
		);
	}
}
