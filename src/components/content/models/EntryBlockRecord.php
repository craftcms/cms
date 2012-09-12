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

	public function defineRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle', 'sectionId'), 'unique' => true)
		);
	}
}
