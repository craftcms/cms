<?php
namespace Blocks;

/**
 *
 */
class Info extends BaseModel
{
	protected $tableName = 'info';

	protected $attributes = array(
		'edition' => array('type' => AttributeType::Enum, 'values' => array('Personal','Standard','Pro'), 'required' => true),
		'version' => AttributeType::Version,
		'build'   => array('type' => AttributeType::Int, 'required' => true, 'unsigned' => true)
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
