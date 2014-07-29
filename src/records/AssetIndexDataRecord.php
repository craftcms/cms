<?php
namespace Craft;

/**
 * Class AssetIndexDataRecord
 *
 * @package craft.app.records
 */
class AssetIndexDataRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetindexdata';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'sessionId' 	=> array(ColumnType::Char, 'length' => 36, 'required' => true, 'default' => ''),
			'sourceId' 		=> array(AttributeType::Number, 'required' => true),
			'offset'  		=> array(AttributeType::Number, 'required' => true),
			'uri'  			=> array(ColumnType::Varchar, 'maxLength' => 255),
			'size' 			=> array(AttributeType::Number),
			'recordId'		=> array(AttributeType::Number),

		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sessionId', 'sourceId', 'offset'), 'unique' => true),
		);
	}
}
