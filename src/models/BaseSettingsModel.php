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
		'key'   => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'value' => AttributeType::Text
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
}
