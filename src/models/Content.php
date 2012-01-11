<?php

class Content extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
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

		// add all BaseModels that have content

		return $hasMany;
	}

	public function getAttributes()
	{
		$attributes = array(
			'language_code' => array('type' => AttributeType::String, 'maxSize' => 5, 'required' => true)
		);

		// add SiteHandle_BlockHandle columns?

		return $attributes;
	}
}
