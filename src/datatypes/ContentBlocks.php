<?php

class ContentBlocks extends BlocksDataType
{
	public function getHasMany()
	{
		$hasMany = array();

		// add all datatypes that have blocks

		return $hasMany;
	}

	private static $attributes = array(
		'handle'       => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'        => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'class'        => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'instructions' => array('type' => AttributeType::Text)
	);
}
