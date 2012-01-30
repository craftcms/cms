<?php
namespace Blocks;

/**
 * @abstract
 */
abstract class BaseSettingsModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

	protected $attributes = array(
		'key'   => array('type' => AttributeType::String, 'maxLength' => 100, 'required' => true),
		'value' => array('type' => AttributeType::Text)
	);

	protected $indexes = array(
		array('columns' => 'key', 'unique' => true)
	);

	/**
	 * Dynamically set $this->belongsTo from $this->foreignKey and $this->model, if they're set
	 */
	public function init()
	{
		if (!empty($this->foreignKey) && !empty($this->model))
		{
			$this->belongsTo = array(
				$this->foreignKey => array('model' => $this->model, 'required' => true)
			);

			$this->indexes = array(
				array('columns' => array($this->foreignKey.'_id', 'key'), 'unique' => true)
			);
		}
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
