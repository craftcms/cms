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
			'postDate'   => array(AttributeType::DateTime, 'required' => true, 'default' => new DateTime()),
			'expiryDate' => AttributeType::DateTime,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'author'  => array(static::BELONGS_TO, 'UserRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'entryTagEntries' => array(static::HAS_MANY, 'EntryTagEntryRecord', 'entryId'),
		);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$relations['versions'] = array(static::HAS_MANY, 'EntryVersionRecord', 'elementId');
		}

		return $relations;
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId')),
			array('columns' => array('postDate')),
			array('columns' => array('expiryDate')),
		);
	}
}
