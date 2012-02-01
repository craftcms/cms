<?php
namespace Blocks;

/**
 *
 */
class Entry extends BaseModel
{
	protected $tableName = 'entries';

	protected $attributes = array(
		'slug'        => AttributeType::Handle,
		'full_uri'    => array('type' => AttributeType::Varchar, 'maxLength' => 1000, 'unique' => true),
		'post_date'   => AttributeType::Int,
		'expiry_date' => AttributeType::Int,
		'sort_order'  => array('type' => AttributeType::Int, 'unsigned' => true),
		'enabled'     => array('type' => AttributeType::Boolean, 'default' => true),
		'archived'    => AttributeType::Boolean
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
