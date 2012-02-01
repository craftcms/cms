<?php
namespace Blocks;

/**
 * @abstract
 */
abstract class BaseBlocksModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'required'   => AttributeType::Boolean,
		'sort_order' => AttributeType::SortOrder
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model
	 */
	public function init()
	{
		$this->belongsTo = array(
			$this->foreignKey => array('model' => $this->model, 'required' => true),
			'block'           => array('model' => 'ContentBlock', 'required' => true)
		);

		$this->indexes = array(
			array('columns' => array($this->foreignKey.'_id', 'block_id'), 'unique' => true)
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
