<?php
namespace Blocks;

/**
 * Stores entry titles
 */
class EntryTitleRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entrytitles';
	}

	public function defineAttributes()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'language' => array(AttributeType::Language, 'required' => true),
			/* end BLOCKSPRO ONLY */
			'title'    => array(AttributeType::String, 'required' => true),
		);
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			/* BLOCKS ONLY */
			array('columns' => array('title', 'entryId'), 'unique' => true),
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			array('columns' => array('title', 'entryId', 'language'), 'unique' => true),
			/* end BLOCKSPRO ONLY */
		);
	}
}
