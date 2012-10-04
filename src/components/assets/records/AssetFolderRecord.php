<?php
namespace Blocks;

/**
 *
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
	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'fullPath' => array(AttributeType::String, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'parent' => array(static::BELONGS_TO, 'AssetFolderRecord'),
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true),
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
