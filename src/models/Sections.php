<?php

class Sections extends BaseModel
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

	protected $hasCustomBlocks = true;

	protected $hasMany = array(
		'children' => array('model' => 'Sections', 'foreignKey' => 'parent')
	);

	protected $belongsTo = array(
		'parent' => array('model' => 'Sections'),
		'site'   => array('model' => 'Sites', 'required' => true)
	);

	protected $attributes = array(
		'handle'      => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true),
		'label'       => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true),
		'url_format'  => array('type' => AttributeType::String, 'maxLength' => 250),
		'max_entries' => array('type' => AttributeType::Integer),
		'template'    => array('type' => AttributeType::String, 'maxLength' => 500),
		'sortable'    => array('type' => AttributeType::Boolean, 'required' => true)
	);
}
