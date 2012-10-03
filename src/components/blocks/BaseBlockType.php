<?php
namespace Blocks;

/**
 * Block base type class
 */
abstract class BaseBlockType extends BaseComponent
{
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
