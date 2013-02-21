<?php
namespace Blocks;

/**
 * Entry tag-entries relation record class
 */
class EntryTagEntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrytags_entries';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'tag' => array(static::BELONGS_TO, 'EntryTagRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
