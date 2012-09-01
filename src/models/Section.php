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
			'name'        => PropertyType::Name,
			'handle'      => PropertyType::Handle,
			'has_urls'    => array(PropertyType::Boolean, 'default' => true),
			'url_format'  => PropertyType::Varchar,
			'template'    => PropertyType::Template,
		);
	}

	protected function getRelations()
	{
		return array(
			'parent'      => array(static::BELONGS_TO, 'Section'),
			'blocks'      => array(static::HAS_MANY, 'SectionBlock', 'section_id', 'order' => 'blocks.sort_order'),
			'children'    => array(static::HAS_MANY, 'Section', 'parent_id'),
			'entries'     => array(static::HAS_MANY, 'Entry', 'section_id'),
			'totalBlocks' => array(static::STAT, 'SectionBlock', 'section_id'),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}
}
