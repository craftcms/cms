<?php
namespace Blocks;

/**
 *
 */
class EntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entries';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'slug'       => array(AttributeType::Slug, 'required' => true),
			'uri'        => array(AttributeType::String, 'maxLength' => 150, 'unique' => true),
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
			'enabled'    => AttributeType::Bool,
			'archived'   => AttributeType::Bool,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations['author'] = array(static::BELONGS_TO, 'UserRecord', 'required' => true);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$relations['section']  = array(static::BELONGS_TO, 'SectionRecord', 'required' => true);
			$relations['versions'] = array(static::HAS_MANY, 'EntryVersionRecord', 'entryId');
		}

		$relations['entryTagEntries'] = array(static::HAS_MANY, 'EntryTagEntryRecord', 'entryId');

		return $relations;
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$indexes[] = array('columns' => array('slug','sectionId'), 'unique' => true);
		}
		else
		{
			$indexes[] = array('columns' => array('slug'), 'unique' => true);
		}

		return $indexes;
	}
}
