<?php

/**
 * @abstract
 */
abstract class bBaseContentModel extends bBaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'num'    => array('type' => bAttributeType::Integer, 'required' => true, 'unsigned' => true),
		'label'  => array('type' => bAttributeType::String, 'maxLength' => 150),
		'active' => array('type' => bAttributeType::Boolean, 'required' => true),
		'type'   => array('type' => bAttributeType::Enum, 'values' => 'published,draft,autosave', 'default' => 'draft', 'required' => true),
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => $this->model,
			'content'         => 'bContent'
		);
	}

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
