<?php
namespace Blocks;

/**
 *
 */
class SectionRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sections';
	}

	public function defineAttributes()
	{
		return array(
			'name'      => array(AttributeType::Name, 'required' => true),
			'handle'    => array(AttributeType::Handle, 'maxLength' => 45, 'required' => true),
			'hasUrls'   => array(AttributeType::Bool, 'default' => true),
			'urlFormat' => AttributeType::String,
			'template'  => AttributeType::Template,
		);
	}

	public function defineRelations()
	{
		return array(
			'blocks'      => array(static::HAS_MANY, 'EntryBlockRecord', 'sectionId', 'order' => 'blocks.sortOrder'),
			'entries'     => array(static::HAS_MANY, 'EntryRecord', 'sectionId'),
			'totalBlocks' => array(static::STAT, 'EntryBlockRecord', 'sectionId'),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
