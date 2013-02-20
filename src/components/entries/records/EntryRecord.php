<?php
namespace Blocks;

/**
 * Entry record class
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
			'type'       => array(AttributeType::ClassName, 'required' => true),
			'postDate'   => array(AttributeType::DateTime, 'required' => true, 'default' => new DateTime()),
			'expiryDate' => AttributeType::DateTime,
			'enabled'    => array(AttributeType::Bool, 'default' => true),
			'archived'   => array(AttributeType::Bool, 'default' => false),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'i18n'            => array(static::HAS_ONE, 'EntryLocalizationRecord', 'entryId', 'condition' => 'i18n.locale=:locale', 'params' => array(':locale' => blx()->language)),
			'content'         => array(static::HAS_ONE, 'EntryContentRecord', 'entryId', 'condition' => 'content.locale=:locale', 'params' => array(':locale' => blx()->language)),
			'entryTagEntries' => array(static::HAS_MANY, 'EntryTagEntryRecord', 'entryId'),
		);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$relations['versions'] = array(static::HAS_MANY, 'EntryVersionRecord', 'entryId');
		}

		return $relations;
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('type')),
			array('columns' => array('postDate')),
			array('columns' => array('expiryDate')),
			array('columns' => array('enabled')),
			array('columns' => array('archived')),
		);
	}
}
