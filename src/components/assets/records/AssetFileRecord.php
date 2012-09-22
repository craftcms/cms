<?php
namespace Blocks;

/**
 *
 */
class AssetFileRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'assetfiles';
	}

	public function defineAttributes()
	{
		return array(
			'filename' => array(AttributeType::String, 'required' => true),
			'kind'     => array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'width'    => array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'height'   => array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'size'     => array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
		);
	}

	public function defineRelations()
	{
		return array(
			'folder' => array(static::BELONGS_TO, 'AssetFolderRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('filename', 'folderId'), 'unique' => true),
		);
	}
}
