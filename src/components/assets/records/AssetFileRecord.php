<?php
namespace Blocks;

/**
 * TODO: create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 */
class AssetFileRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetfiles';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'filename'		=> array(AttributeType::String, 'required' => true),
			'kind'			=> array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'width'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'height'		=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'size'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::Int),
			'dateModified'	=> array(AttributeType::DateTime),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'source'  => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'folder'  => array(static::BELONGS_TO, 'AssetFolderRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'content' => array(static::HAS_ONE, 'AssetContentRecord', 'fileId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('filename', 'folderId'), 'unique' => true),
		);
	}
}
