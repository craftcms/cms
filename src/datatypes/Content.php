<?php

class Content extends BlocksDataType
{
	public function getHasMany()
	{
		$hasMany = array();

		// add all datatypes that have content

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
