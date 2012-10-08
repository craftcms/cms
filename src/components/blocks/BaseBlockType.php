<?php
namespace Blocks;

/**
 * Block type base class
 */
abstract class BaseBlockType extends BaseComponent
{
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
	 * @return string|array
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
	 * Preprocesses the input value before the entity is saved.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function preprocessInputValue($settings)
	{
		return $settings;
	}

	/**
	 * Performs any additional actions after the entity has been saved.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function postprocessInputValue($settings)
	{
		return $settings;
	}

}
