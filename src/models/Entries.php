<?php

/**
 *
 */
class Entries extends BaseModel
{
	protected $attributes = array(
		'slug'        => array('type' => bAttributeType::String),
		'full_uri'    => array('type' => bAttributeType::String, 'maxLength' => 1000),
		'post_date'   => array('type' => bAttributeType::Integer),
		'expiry_date' => array('type' => bAttributeType::Integer),
		'sort_order'  => array('type' => bAttributeType::Integer, 'unsigned' => true),
		'enabled'     => array('type' => bAttributeType::Boolean, 'required' => true, 'default' => true, 'unsigned' => true),
		'archived'    => array('type' => bAttributeType::Boolean, 'required' => true, 'default' => false, 'unsigned' => true)
	);

	protected $belongsTo = array(
		'parent'  => array('model' => 'Entries'),
		'section' => array('model' => 'Sections', 'required' => true),
		'author'  => array('model' => 'Users', 'required' => true)
	);

	protected $hasContent = array(
		'content' => array('through' => 'EntryContent', 'foreignKey' => 'entry')
	);

	protected $hasMany = array(
		'children' => array('model' => 'Entries', 'foreignKey' => 'parent')
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
