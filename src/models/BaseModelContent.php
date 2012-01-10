<?php

abstract class BaseModelContent extends BaseModel
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

	protected $attributes = array(
		'num'    => array('type' => AttributeType::Integer, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 150),
		'active' => array('type' => AttributeType::Boolean, 'required' => true),
		'type'   => array('type' => AttributeType::Enum, 'values' => 'published,draft,autosave', 'default' => 'draft', 'required' => true),
	);

	public function getBelongsTo()
	{
		return array(
			strtolower($this->_blocksModel) => $this->_blocksModel,
			'content' => 'Content'
		);
	}
}
