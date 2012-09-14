<?php
namespace Blocks;

/**
 * Block base class
 */
abstract class BaseBlock extends BaseComponent implements IBlock
{
	protected $componentType = 'Block';

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * Returns the block's input HTML.
	 *
	 * @abstract
	 * @param mixed $package
	 * @param string $handle
	 * @return string
	 */
	abstract public function getInputHtml($package, $handle);
}
