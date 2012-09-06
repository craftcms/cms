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

	protected function defineAttributes()
	{
		return array(
			'name'      => AttributeType::Name,
			'handle'    => AttributeType::Handle,
			'hasUrls'   => array(AttributeType::Boolean, 'default' => true),
			'urlFormat' => AttributeType::Varchar,
			'template'  => AttributeType::Template,
		);
	}

	protected function defineRelations()
	{
		return array(
			'parent'      => array(static::BELONGS_TO, 'SectionRecord'),
			'blocks'      => array(static::HAS_MANY, 'EntryBlockRecord', 'sectionId', 'order' => 'blocks.sortOrder'),
			'children'    => array(static::HAS_MANY, 'SectionRecord', 'parentId'),
			'entries'     => array(static::HAS_MANY, 'EntryRecord', 'sectionId'),
			'totalBlocks' => array(static::STAT, 'EntryBlockRecord', 'sectionId'),
		);
	}

	protected function defineIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
