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

	protected function defineRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
		);
	}

	protected function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId', 'handle'), 'unique' => true)
		);
	}
}
