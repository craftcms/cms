<?php
namespace craft\app\records;

use craft\app\Craft;

/**
 * Class Entry record.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     3.0
 */
class Entry extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'entries';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'element' => array(static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'section' => array(static::BELONGS_TO, 'Section', 'required' => true, 'onDelete' => static::CASCADE),
			'type'    => array(static::BELONGS_TO, 'EntryType', 'onDelete' => static::CASCADE),
			'author'  => array(static::BELONGS_TO, 'User', 'onDelete' => static::CASCADE),
		);

		if (craft()->getEdition() == Craft::Pro)
		{
			$relations['versions'] = array(static::HAS_MANY, 'EntryVersion', 'elementId');
		}

		return $relations;
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId')),
			array('columns' => array('typeId')),
			array('columns' => array('postDate')),
			array('columns' => array('expiryDate')),
		);
	}

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'postDate'),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
		);
	}
}
