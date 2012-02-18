<?php
namespace Blocks;

/**
 * @abstract
 */
abstract class BaseContentModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'num'    => array('type' => AttributeType::Int, 'required' => true, 'unsigned' => true),
		'name'   => AttributeType::Name,
		'active' => AttributeType::Boolean,
		'type'   => array('type' => AttributeType::Enum, 'values' => array('published','draft','autosave'), 'default' => 'draft', 'required' => true),
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function __construct($scenario = 'insert')
	{
		$this->belongsTo = array(
			$this->foreignKey => array('model' => $this->model, 'required' => true),
			'content'         => array('model' => 'Content', 'required' => true)
		);

		parent::__construct($scenario);
	}
}
