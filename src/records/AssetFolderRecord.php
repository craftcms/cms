<?php
namespace Craft;

/**
 * Class AssetFolderRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class AssetFolderRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetfolders';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'path'     => array(AttributeType::String),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'parent' => array(static::BELONGS_TO, 'AssetFolderRecord', 'onDelete' => static::CASCADE),
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => false, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'parentId', 'sourceId'), 'unique' => true),
		);
	}
}
