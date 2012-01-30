<?php
namespace Blocks;

/**
 *
 */
class Section extends BaseModel
{
	protected $tableName = 'sections';

	protected $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true),
		'handle'      => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true),
		'url_format'  => array('type' => AttributeType::String),
		'max_entries' => array('type' => AttributeType::Integer, 'unsigned' => true),
		'template'    => array('type' => AttributeType::String, 'maxLength' => 500),
		'sortable'    => array('type' => AttributeType::Boolean, 'required' => true, 'unsigned' => true)
	);

	protected $belongsTo = array(
		'parent' => array('model' => 'Section'),
		'site'   => array('model' => 'Site', 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'SectionBlock', 'foreignKey' => 'section')
	);

	protected $hasMany = array(
		'children' => array('model' => 'Section', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('site_id', 'handle'), 'unique' => true),
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
