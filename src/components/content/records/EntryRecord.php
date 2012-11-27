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
		$attributes['slug'] = array(AttributeType::Slug, 'required' => true);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$attributes['uri'] = array(AttributeType::String, 'maxLength' => 150, 'unique' => true);
		}

		$attributes['postDate'] = AttributeType::DateTime;
		$attributes['expiryDate'] = AttributeType::DateTime;
		$attributes['enabled'] = AttributeType::Bool;
		$attributes['archived'] = AttributeType::Bool;

		return $attributes;
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations['author'] = array(static::BELONGS_TO, 'UserRecord', 'required' => true);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$relations['section']  = array(static::BELONGS_TO, 'SectionRecord', 'required' => true, 'onDelete' => static::CASCADE);
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
