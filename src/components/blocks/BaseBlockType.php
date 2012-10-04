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
	 * @return string
	 */
	abstract public function getInputHtml($name, $value);
}
