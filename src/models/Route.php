<?php
namespace Blocks;

/**
 *
 */
class Route extends BaseModel
{
	protected $tableName = 'routes';

	protected $attributes = array(
		'route'      => array('type' => AttributeType::Varchar, 'maxLength' => 500, 'required' => true),
		'template'   => array('type' => AttributeType::Template, 'required' => true),
		'sort_order' => AttributeType::SortOrder
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('site_id','route'), 'unique' => true)
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
