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
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['language'] = array(AttributeType::Language, 'required' => true);
		}

		$attributes['title'] = array(AttributeType::String, 'required' => true);

		return $attributes;
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
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$indexes[] = array('columns' => array('entryId', 'language'), 'unique' => true);
		}
		else
		{
			$indexes[] = array('columns' => array('entryId'), 'unique' => true);
		}

		return $indexes;
	}
}
