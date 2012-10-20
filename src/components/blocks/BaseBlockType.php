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
	 * @var BaseEntityModel
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
	 * @return string
	 */
	abstract public function getInputHtml($name, $value);

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @return mixed
	 */
	public function getPostData()
	{
		$blockHandle = $this->model->handle;
		$value = $this->entity->getRawContent($blockHandle);
		return $this->prepPostData($value);
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		return $value;
	}

	/**
	 * Performs any actions before a block is saved.
	 */
	public function onBeforeSave()
	{
	}

	/**
	 * Performs any actions after a block is saved.
	 */
	public function onAfterSave()
	{
	}

	/**
	 * Performs any additional actions after the entity has been saved.
	 */
	public function onAfterEntitySave()
	{
	}

	/**
	 * Preps the block value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		return $value;
	}
}
