<?php

class ContentBlocks extends BlocksModel
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

	public function getHasMany()
	{
		$hasMany = array();

		// add all BlocksModels that have blocks

		return $hasMany;
	}

	protected static $attributes = array(
		'handle'       => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'        => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'class'        => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'instructions' => array('type' => AttributeType::Text)
	);
}
