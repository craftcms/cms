<?php

class Entries extends BlocksModel
{
	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected static $hasContent = true;

	protected static $hasMany = array(
		'children' => 'Entries.parent'
	);

	protected static $belongsTo = array(
		'parent'  => 'Entries',
		'section' => 'Sections',
		'author'  => 'Users'
	);

	protected static $attributes = array(
		'slug'        => array('type' => AttributeType::String, 'maxSize' => 250),
		'full_uri'    => array('type' => AttributeType::String, 'maxSize' => 1000),
		'post_date'   => array('type' => AttributeType::Integer),
		'expiry_date' => array('type' => AttributeType::Integer),
		'sort_order'  => array('type' => AttributeType::Integer),
		'enabled'     => array('type' => AttributeType::Boolean, 'required' => true, 'default' => true),
		'archived'    => array('type' => AttributeType::Boolean, 'required' => true, 'default' => false)
	);
}
