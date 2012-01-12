<?php

/**
 *
 */
class Content extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function getHasMany()
	{
		$hasMany = array();

		// add all BaseModels that have content

		return $hasMany;
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		$attributes = array(
			'language_code' => array('type' => AttributeType::String, 'maxSize' => 5, 'required' => true)
		);

		// add SiteHandle_BlockHandle columns?

		return $attributes;
	}
}
