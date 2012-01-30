<?php

/**
 *
 */
class bAsset extends bBaseModel
{
	protected $tableName = 'assets';

	protected $attributes = array(
		'path'      => array('type' => bAttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'filename'  => array('type' => bAttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'extension' => array('type' => bAttributeType::Char, 'maxLength' => 15, 'required' => false)
	);

	protected $belongsTo = array(
		'folder' => array('model' => 'bAssetFolder', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'bAssetBlock', 'foreignKey' => 'asset')
	);

	protected $hasContent = array(
		'content' => array('through' => 'bAssetContent', 'foreignKey' => 'asset')
	);

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
