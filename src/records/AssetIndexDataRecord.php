<?php
namespace Craft;

/**
 * Class AssetIndexDataRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.records
 * @since     1.0
 */
class AssetIndexDataRecord extends BaseRecord
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
		return 'assetindexdata';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sessionId', 'sourceId', 'offset'), 'unique' => true),
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
			'sessionId' 	=> array(ColumnType::Char, 'length' => 36, 'required' => true, 'default' => ''),
			'sourceId' 		=> array(AttributeType::Number, 'required' => true),
			'offset'  		=> array(AttributeType::Number, 'required' => true),
			'uri'  			=> array(ColumnType::Varchar, 'maxLength' => 255),
			'size' 			=> array(ColumnType::BigInt, 'unsigned' => true),
			'recordId'		=> array(AttributeType::Number),
		);
	}
}
