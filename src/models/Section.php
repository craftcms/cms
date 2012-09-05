<?php
namespace Blocks;

/**
 *
 */
class Section extends BaseModel
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
			'parent'      => array(static::BELONGS_TO, 'Section'),
			'blocks'      => array(static::HAS_MANY, 'EntryBlock', 'sectionId', 'order' => 'blocks.sortOrder'),
			'children'    => array(static::HAS_MANY, 'Section', 'parentId'),
			'entries'     => array(static::HAS_MANY, 'Entry', 'sectionId'),
			'totalBlocks' => array(static::STAT, 'EntryBlock', 'sectionId'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
