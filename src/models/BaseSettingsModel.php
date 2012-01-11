<?php

abstract class BaseSettingsModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

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

	protected $attributes = array(
		'key'   => array('type' => AttributeType::String, 'maxSize' => 100, 'required' => true),
		'value' => array('type' => AttributeType::Text)
	);

	public function getBelongsTo()
	{
		if (isset($this->foreignKey) && isset($this->model))
			return array(
				$this->foreignKey => array('model' => $this->model, 'required' => true)
			);

		return array();
	}
}
