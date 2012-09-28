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
		$relations = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$relations['section'] = array(static::BELONGS_TO, 'SectionRecord', 'required' => true);
		}

		return $relations;
	}

	public function defineIndexes()
	{
		$indexes = array();

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$indexes[] = array('columns' => array('handle', 'sectionId'), 'unique' => true);
		}

		return $indexes;
	}
}
