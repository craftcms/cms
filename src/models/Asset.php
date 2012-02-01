<?php
namespace Blocks;

/**
 *
 */
class Asset extends BaseModel
{
	protected $tableName = 'assets';

	protected $attributes = array(
		'path'      => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'filename'  => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'required' => true),
		'extension' => array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => false)
	);

	protected $belongsTo = array(
		'folder' => array('model' => 'AssetFolder', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'AssetBlock', 'foreignKey' => 'asset')
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
