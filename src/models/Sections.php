<?php

class Sections extends BlocksModel
{
	protected static $hasCustomBlocks = true;

	protected static $hasMany = array(
		'children' => 'Sections.parent'
	);

	protected static $belongsTo = array(
		'parent' => 'Sections',
		'site'   => 'Sites'
	);

	protected static $attributes = array(
		'handle'      => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true),
		'label'       => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true),
		'url_format'  => array('type' => AttributeType::String, 'maxLength' => 250),
		'max_entries' => array('type' => AttributeType::Integer),
		'template'    => array('type' => AttributeType::String, 'maxLength' => 500),
		'sortable'    => array('type' => AttributeType::Boolean, 'required' => true)
	);
}
