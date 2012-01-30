<?php
namespace Blocks;

/**
 *
 */
class ContentBlock extends BaseModel
{
	protected $tableName = 'contentblocks';

	/**
	 * @return array
	 */
	protected $attributes = array(
		'name'         => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true),
		'handle'       => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true),
		'class'        => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true),
		'instructions' => array('type' => AttributeType::Text)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('site_id','handle'), 'unique' => true)
	);

	/**
	 * Returns an instance of the specified model
	 * @static
	 * @param string $class
	 * @return object The model instance
	*/
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
