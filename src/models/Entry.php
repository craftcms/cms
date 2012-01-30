<?php
namespace Blocks;

/**
 *
 */
class Entry extends BaseModel
{
	protected $tableName = 'entries';

	protected $attributes = array(
		'slug'        => array('type' => AttributeType::String),
		'full_uri'    => array('type' => AttributeType::String, 'maxLength' => 1000, 'unique' => true),
		'post_date'   => array('type' => AttributeType::Integer),
		'expiry_date' => array('type' => AttributeType::Integer),
		'sort_order'  => array('type' => AttributeType::Integer, 'unsigned' => true),
		'enabled'     => array('type' => AttributeType::Boolean, 'required' => true, 'default' => true, 'unsigned' => true),
		'archived'    => array('type' => AttributeType::Boolean, 'required' => true, 'default' => false, 'unsigned' => true)
	);

	protected $belongsTo = array(
		'parent'  => array('model' => 'Entry'),
		'section' => array('model' => 'Section', 'required' => true),
		'author'  => array('model' => 'User', 'required' => true)
	);

	protected $hasContent = array(
		'content' => array('through' => 'EntryContent', 'foreignKey' => 'entry')
	);

	protected $hasMany = array(
		'children' => array('model' => 'Entry', 'foreignKey' => 'parent')
	);

	protected $indexes = array(
		array('columns' => array('section_id','slug'), 'unique' => true),
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
