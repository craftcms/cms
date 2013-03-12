<?php
namespace Craft;

/**
 * Entry tag record class
 */
class EntryTagRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entrytags';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'   => array(AttributeType::Name, 'required' => true),
			'count'  => array(AttributeType::Number, 'unsigned' => true, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entryTagEntries' => array(static::HAS_MANY, 'EntryTagEntryRecord', 'entryTagId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true)
		);
	}
}
