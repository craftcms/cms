<?php

/**
 * @abstract
 */
abstract class BaseBlocksModel extends BaseModel
{
	protected $foreignKey;
	protected $model;

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

	protected $attributes = array(
		'required'     => array('type' => AttributeType::Boolean, 'required' => true),
		'sort_order'   => array('type' => AttributeType::Integer, 'required' => true)
	);

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function getBelongsTo()
	{
		return array(
			$this->foreignKey => $this->model,
			'block'           => 'ContentBlocks'
		);
	}
}
