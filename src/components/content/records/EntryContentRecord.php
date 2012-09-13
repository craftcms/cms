<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entrycontent';
	}

	public function defineAttributes()
	{
		$attributes = array();

		$blocks = blx()->content->getEntryBlocks();
		foreach ($blocks as $block)
		{
			$attributes[$block->record->handle] = $block->defineContentAttribute();
		}

		return $attributes;
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}
}
