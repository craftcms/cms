<?php
namespace Blocks;

/**
 * Stores entry titles
 */
class EntryTitleRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrytitles';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'language' => array(AttributeType::Language, 'required' => true),
			'title'    => array(AttributeType::String, 'required' => true),
		);
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

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('title', 'entryId', 'language')),
			array('columns' => array('entryId', 'language'), 'unique' => true),
		);
	}
}
