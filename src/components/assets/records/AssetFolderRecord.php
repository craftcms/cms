<?php
namespace Blocks;

/**
 *
 */
class AssetFolderRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetfolders';
	}

	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'fullPath' => array(AttributeType::String, 'required' => true),
		);
	}

	public function defineRelations()
	{
		return array(
			'parent' => array(static::BELONGS_TO, 'AssetFolderRecord'),
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'parentId', 'sourceId'), 'unique' => true),
		);
	}
}
