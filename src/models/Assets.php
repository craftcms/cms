<?php

/**
 *
 */
class Assets extends BaseModel
{
	protected $attributes = array(
		'path'      => array('type' => bAttributeType::String, 'maxLength' => 1000, 'required' => true),
		'filename'  => array('type' => bAttributeType::String, 'maxLength' => 1000, 'required' => true),
		'extension' => array('type' => bAttributeType::String, 'maxLength' => 50, 'required' => false)
	);

	protected $belongsTo = array(
		'folder' => array('model' => 'AssetFolders', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'AssetBlocks', 'foreignKey' => 'asset')
	);

	protected $hasContent = array(
		'content' => array('through' => 'AssetContent', 'foreignKey' => 'asset')
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
