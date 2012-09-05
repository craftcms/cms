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

	protected function getProperties()
	{
		return array(
			'name'      => PropertyType::Name,
			'handle'    => PropertyType::Handle,
			'hasUrls'   => array(PropertyType::Boolean, 'default' => true),
			'urlFormat' => PropertyType::Varchar,
			'template'  => PropertyType::Template,
		);
	}

	protected function getRelations()
	{
		return array(
			'parent'      => array(static::BELONGS_TO, 'SectionRecord'),
			'blocks'      => array(static::HAS_MANY, 'EntryBlockRecord', 'sectionId', 'order' => 'blocks.sortOrder'),
			'children'    => array(static::HAS_MANY, 'SectionRecord', 'parentId'),
			'entries'     => array(static::HAS_MANY, 'EntryRecord', 'sectionId'),
			'totalBlocks' => array(static::STAT, 'EntryBlockRecord', 'sectionId'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
