<?php
namespace Blocks;

/**
 * Stores entry versions
 */
class EntryVersionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entryversions';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'language' => array(AttributeType::Language, 'required' => true),
			'notes'    => array(AttributeType::String, 'column' => ColumnType::TinyText),
			'data'     => array(AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry'   => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
			'creator' => array(static::BELONGS_TO, 'UserRecord', 'required' => true)
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('entryId', 'language')),
		);
	}
}
