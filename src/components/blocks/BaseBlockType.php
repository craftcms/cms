<?php
namespace Blocks;

/**
 * Block type base class
 */
abstract class BaseBlockType extends BaseComponent
{
	/**
	 * The enttiy that the block is associated with.
	 * Set by the service classes.
	 *
	 * @var BaseBlockEntityModel
	 */
	public $entity;

	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'BlockType';

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @abstract
	 * @param string $name
	 * @param mixed  $value
	 * @param int|null $entityId;
	 * @return string
	 */
	abstract public function getInputHtml($name, $value, $entityId = null);

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @return mixed
	 */
	public function getInputValue()
	{
		$value = $this->entity->getBlockValueById($this->model->id);
		return $this->preprocessInputValue($value);
	}

	/**
	 * Preprocesses the input value before it is saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function preprocessInputValue($value)
	{
		return $value;
	}

	/**
	 * Performs any additional actions after the entity has been saved.
	 */
	public function onAfterEntitySave()
	{
	}

}
