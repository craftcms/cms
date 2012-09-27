<?php
namespace Blocks;

/**
 *
 */
class EntryRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entries';
	}

	public function defineAttributes()
	{
		return array(
			'slug'          => array(AttributeType::Slug, 'required' => true),
			'uri'           => array(AttributeType::String, 'maxLength' => 150, 'unique' => true),
			'postDate'      => AttributeType::DateTime,
			/* BLOCKSPRO ONLY */
			'expiryDate'    => AttributeType::DateTime,
			'sortOrder'     => array(AttributeType::Number, 'unsigned' => true),
			'latestDraft'   => array(AttributeType::Number, 'unsigned' => true),
			'latestVersion' => array(AttributeType::Number, 'unsigned' => true),
			/* end BLOCKSPRO ONLY */
			'archived'      => AttributeType::Bool,
		);
	}

	/* BLOCKSPRO ONLY */
	public function defineRelations()
	{
		return array(
			'author'   => array(static::BELONGS_TO, 'UserRecord', 'required' => true),
			'section'  => array(static::BELONGS_TO, 'SectionRecord', 'required' => true),
			'versions' => array(static::HAS_MANY, 'EntryVersionRecord', 'entryId'),
		);
	}

	/* end BLOCKSPRO ONLY */
	public function defineIndexes()
	{
		return array(
			/* BLOCKS ONLY */
			array('columns' => array('slug'), 'unique' => true),
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			array('columns' => array('slug','sectionId'), 'unique' => true),
			/* end BLOCKSPRO ONLY */
		);
	}
}
